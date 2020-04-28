<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Exception;
use phpDocumentor\Reflection\Types\Integer;
use Bluerhinos\phpMQTT as phpmqtt;

class BookingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function readBookingActiveByUserId($userId) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $booking = DB::table('TR_Booking')
            ->join('MS_User', 'MS_User.Id', '=', 'TR_Booking.UserId')
            ->join('MS_ZoneDetail', 'MS_ZoneDetail.Id', '=', 'TR_Booking.ZoneDetailId')
            ->join('MS_Zone', 'MS_Zone.Id', '=', 'MS_ZoneDetail.ZoneId')
            ->join('MS_Device', 'MS_Device.Id', '=', 'MS_ZoneDetail.DeviceId')
            ->where('TR_Booking.UserId', $userId)
            ->whereIn('TR_Booking.Status', [0,1])
            ->orderBy('TR_Booking.InputTime', 'DESC')
            ->select(
                'TR_Booking.Id as Id',
                'TR_Booking.UserId as UserId',
                'MS_User.FullName as UserFullname',
                'TR_Booking.ZoneDetailId as ZoneDetailId',
                'MS_ZoneDetail.Name as ZoneDetailName',
                'MS_ZoneDetail.ZoneId as ZoneId',
                'MS_Zone.Name as ZoneName',
                'MS_ZoneDetail.DeviceId as DeviceId',
                'MS_Device.Name as DeviceName',
                'TR_Booking.Status as StatusId',
                DB::raw('(CASE WHEN TR_Booking.Status = 0 THEN \'OPEN\' WHEN TR_Booking.Status = 1 THEN \'IN PROGRESS\' WHEN TR_Booking.Status = 2 THEN \'DONE\' ELSE \'EXPIRED\' END) as StatusName'),
                DB::raw('ADDTIME(CONVERT_TZ(TR_Booking.InputTime,\'+00:00\',\'+07:00\'), "2:0:0") as ExpiredTime'),
                DB::raw('CONVERT_TZ(TR_Booking.ModifTime,\'+00:00\',\'+07:00\') as LastAction'),
                DB::raw('TIME_TO_SEC(TIMEDIFF(CONVERT_TZ(CURRENT_TIMESTAMP,\'+00:00\',\'+07:00\'), CONVERT_TZ(TR_Booking.InputTime,\'+00:00\',\'+07:00\'))) as AllDuration'),
                DB::raw('TIME_TO_SEC(TIMEDIFF(CONVERT_TZ(CURRENT_TIMESTAMP,\'+00:00\',\'+07:00\'), CONVERT_TZ(TR_Booking.ModifTime,\'+00:00\',\'+07:00\'))) as DurationPerAction'),
                DB::raw('null as BookingDetail')
            )
            ->first();

            if (empty($booking)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                if ($booking->StatusId == 0) {
                    //$aDate = explode(':', $booking->AllDuration);
                    //$durationHour = (int)$aDate[0];

                    $UN = DB::table('MS_User')->where('Id', $userId)->select('UserName')->first()->UserName;

                    if ($booking->AllDuration >= 7200) {
                        $affected = DB::table('TR_Booking')->where('Id', $booking->Id)->update([
                            'Status' => 3,
                            'ModifUN' => $UN
                        ]);
            
                        if ($affected < 1)
                            throw new Exception("Update Failed with affected < 1");
            
                        DB::table('TR_BookingDetail')->insert([
                            'BookingId' => $booking->Id,
                            'Status' => 3,
                            'InputUN' => $UN
                        ]);

                        $booking->StatusId = 3;
                        $booking->StatusName = 'EXPIRED';
                        $booking->LastAction = (string)date('Y-m-d H:i:s');
                        $booking->DurationPerAction = 1;
                    }
                }

                $bookingDetail = DB::table('TR_BookingDetail')
                ->join('TR_Booking', 'TR_Booking.Id', '=', 'TR_BookingDetail.BookingId')
                ->where('TR_BookingDetail.BookingId', $booking->Id)
                ->select(
                    'TR_BookingDetail.Id as Id',
                    'TR_BookingDetail.BookingId as BookingId',
                    'TR_BookingDetail.Status as StatusId',
                    DB::raw('(CASE WHEN TR_BookingDetail.Status = 0 THEN \'OPEN\' WHEN TR_BookingDetail.Status = 1 THEN \'IN PROGRESS\' WHEN TR_BookingDetail.Status = 2 THEN \'DONE\' ELSE \'EXPIRED\' END) as StatusName'),
                    DB::raw('CONVERT_TZ(TR_BookingDetail.InputTime,\'+00:00\',\'+07:00\') as ActionTime')
                )
                ->get();
                
                if (!empty($bookingDetail))
                    $booking->BookingDetail = $bookingDetail;

                $response["Data"] = $booking;
                $response["Message"] = "SUCCESS";
            }
                
            $response["Code"] = 0;
            $httpcode = 200;
        } catch(Exception $e) {
            $response["Code"] = 1;
            $response["Error"] = $e->getMessage();
            $response["Message"] = "";
            $response["Data"] = null;
        }

        return response()->json($response, $httpcode);
    }

    public function createBooking(Request $request) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($request->all()))
                throw new Exception("Request Invalid");

            if (empty($request->input('UserId')))
                throw new Exception("Request Id Invalid");

            if (empty($request->input('ZoneDetailId')))
                throw new Exception("Request ZoneDetailId Invalid");

            $userId = $request->input("UserId");
            $zoneDetailId = $request->input("ZoneDetailId");
            $UN = $request->input("UN", null);

            $userIdExist = DB::table('MS_User')->where([
                ['Id', '=', $userId],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($userIdExist < 1)
                throw new Exception("User not Found");

            $zoneDetailIdExist = DB::table('MS_ZoneDetail')->where([
                ['Id', '=', $zoneDetailId],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($zoneDetailIdExist < 1)
                throw new Exception("Zone Detail not Found");

            $zoneDetailAvailable = DB::table('TR_Booking')
            ->where('ZoneDetailId', $zoneDetailId)
            ->whereIn('Status', [0,1])
            ->count();

            if ($zoneDetailAvailable > 0)
                throw new Exception("Zone Detail already use");

            $idBooking = DB::table('TR_Booking')->insertGetId([
                'UserId' => $userId,
                'ZoneDetailId' => $zoneDetailId,
                'Status' => 0,
                'InputUN' => $UN,
                'ModifUN' => $UN
            ]);

            if (empty($idBooking) || $idBooking < 1)
                throw new Exception("Insert Booking Failed");

            DB::table('TR_BookingDetail')->insert([
                'BookingId' => $idBooking,
                'Status' => 0,
                'InputUN' => $UN
            ]);

            $response["Code"] = 0;
            $response["Message"] = "SUCCESS";
            $httpcode = 200;
        } catch(Exception $e) {
            $response["Code"] = 1;
            $response["Error"] = $e->getMessage();
            $response["Message"] = "";
            $response["Data"] = null;
        }

        return response()->json($response, $httpcode);
    }

    public function checkInBooking(Request $request) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($request->all()))
                throw new Exception("Request Invalid");

            if (empty($request->input('UserId')))
                throw new Exception("Request Id Invalid");

            $userId = $request->input("UserId");
            $UN = $request->input("UN", null);

            $userIdExist = DB::table('MS_User')->where([
                ['Id', '=', $userId],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($userIdExist < 1)
                throw new Exception("User not Found");

            $booking = DB::table('TR_Booking')
            ->where([
                ['UserId', '=', $userId],
            ])
            ->orderByDesc('InputTime')
            ->first();

            if ($booking == null || $booking->Status != 0)
                throw new Exception("Booking Status Invalid");

            // ====================================
            // Function MQTT
            $devices = DB::table('MS_Device')
            ->join('MS_ZoneDetail', 'MS_Device.Id', '=', 'MS_ZoneDetail.DeviceId')
            ->where('MS_ZoneDetail.Id', $booking->ZoneDetailId)
            ->select('MS_Device.Code')
            ->first();


            $url = "broker.hivemq.com";
            $port = "1883";
            $topic = "/".$devices->Code."/booking/pulldown";
            $message = $this->getRandomCode(10);
            $clientId = $this->getRandomCode(16);
            
            DB::table('TR_LogTransactionDevice')->insert([
                'RandomCode' => $message,
                'Status' => null
            ]);

            $mqtt = new phpmqtt($url, $port, $clientId);
            if ($mqtt->connect()) {
                $mqtt->publish($topic, $message, 0);
                $mqtt->close();
            } else {
                throw new Exception("Failed Check In");
            }
            $logResult = false;
            for ($i=0; $i < 100000; $i++) { 
                $resultTransaction = DB::table('TR_LogTransactionDevice')->where('RandomCode', $message)->select('Status')->first();
                if ($resultTransaction->Status != null && $resultTransaction->Status == 'SUCCESS') {
                    $logResult = true;
                    break;
                } else if ($resultTransaction->Status != null && $resultTransaction->Status == 'FAILED') {
                    $logResult = false;
                    break;
                }
            }

            if (!$logResult)
                throw new Exception("Failed Check In");
            // ====================================
            
            $affected = DB::table('TR_Booking')->where('Id', $booking->Id)->update([
                'Status' => 1,
                'ModifUN' => $UN
            ]);

            if ($affected < 1)
                throw new Exception("Update Failed with affected < 1");

            DB::table('TR_BookingDetail')->insert([
                'BookingId' => $booking->Id,
                'Status' => 1,
                'InputUN' => $UN
            ]);
            
            $response["Code"] = 0;
            $response["Message"] = "SUCCESS";
            $httpcode = 200;
        } catch(Exception $e) {
            $response["Code"] = 1;
            $response["Error"] = $e->getMessage();
            $response["Message"] = "";
            $response["Data"] = null;
        }

        return response()->json($response, $httpcode);
    }

    public function checkOutBooking(Request $request) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($request->all()))
                throw new Exception("Request Invalid");

            if (empty($request->input('UserId')))
                throw new Exception("Request Id Invalid");

            $userId = $request->input("UserId");
            $UN = $request->input("UN", null);

            $userIdExist = DB::table('MS_User')->where([
                ['Id', '=', $userId],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($userIdExist < 1)
                throw new Exception("User not Found");

            $booking = DB::table('TR_Booking')
            ->where([
                ['UserId', '=', $userId],
            ])
            ->orderByDesc('InputTime')
            ->first();

            if ($booking == null || $booking->Status != 1)
                throw new Exception("Booking Status Invalid");

            // ====================================
            // Function MQTT
            $devices = DB::table('MS_Device')
            ->join('MS_ZoneDetail', 'MS_Device.Id', '=', 'MS_ZoneDetail.DeviceId')
            ->where('MS_ZoneDetail.Id', $booking->ZoneDetailId)
            ->select('MS_Device.Code')
            ->first();


            $url = "broker.hivemq.com";
            $port = "1883";
            $topic = "/".$devices->Code."/booking/pulldown";
            $message = $this->getRandomCode(10);
            $clientId = $this->getRandomCode(16);
            
            DB::table('TR_LogTransactionDevice')->insert([
                'RandomCode' => $message,
                'Status' => null
            ]);

            $mqtt = new phpmqtt($url, $port, $clientId);
            if ($mqtt->connect()) {
                $mqtt->publish($topic, $message, 0);
                $mqtt->close();
            } else {
                throw new Exception("Failed Check Out");
            }
            $logResult = false;
            for ($i=0; $i < 100000; $i++) { 
                $resultTransaction = DB::table('TR_LogTransactionDevice')->where('RandomCode', $message)->select('Status')->first();
                if ($resultTransaction->Status != null && $resultTransaction->Status == 'SUCCESS') {
                    $logResult = true;
                    break;
                } else if ($resultTransaction->Status != null && $resultTransaction->Status == 'FAILED') {
                    $logResult = false;
                    break;
                }
            }

            if (!$logResult)
                throw new Exception("Failed Check Out");
            // ====================================

            $affected = DB::table('TR_Booking')->where('Id', $booking->Id)->update([
                'Status' => 2,
                'ModifUN' => $UN
            ]);

            if ($affected < 1)
                throw new Exception("Update Failed with affected < 1");

            DB::table('TR_BookingDetail')->insert([
                'BookingId' => $booking->Id,
                'Status' => 2,
                'InputUN' => $UN
            ]);
            
            $response["Code"] = 0;
            $response["Message"] = "SUCCESS";
            $httpcode = 200;
        } catch(Exception $e) {
            $response["Code"] = 1;
            $response["Error"] = $e->getMessage();
            $response["Message"] = "";
            $response["Data"] = null;
        }

        return response()->json($response, $httpcode);
    }

    public function updateLogTransactionDevice($randomCode, $status) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $affected = DB::table('TR_LogTransactionDevice')->where('RandomCode', $randomCode)->update([
                'Status' => $status
            ]);

            if ($affected < 1)
                throw new Exception("Update Failed with affected < 1");

            $response["Code"] = 0;
            $response["Error"] = null;
            $response["Message"] = "SUCCESS";
            $response["Data"] = null;
        } catch(Exception $e) {
            $response["Code"] = 1;
            $response["Error"] = $e->getMessage();
            $response["Message"] = "";
            $response["Data"] = null;
        }

        return response()->json($response, $httpcode);
    }

    function getRandomCode($n) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = '';
      
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    } 
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Exception;

class ZoneController extends Controller
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



    public function create(Request $request) {
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

            if (empty($request->input('Code')) || strlen($request->input('Code')) > 5)
                throw new Exception("Request Code Invalid");

            if (empty($request->input('Name')) || strlen($request->input('Name')) > 255)
                throw new Exception("Request Name Invalid");

            $Code = $request->input("Code");
            $Name = $request->input("Name");
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $ZoneCodeExist = DB::table('MS_Zone')->where([
                ['Code', '=', $Code],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($ZoneCodeExist > 0)
                throw new Exception("Code Already Exist");

            DB::table('MS_Zone')->insert([[
                'Code' => $Code,
                'Name' => $Name,
                'StatusFlag' => empty($StatusFlag) ? 1 : $StatusFlag,
                'InputUN' => $UN,
                'ModifUN' => $UN
            ]]);

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

    public function read() {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $zones = DB::table('MS_Zone')->where("StatusFlag", 1)->select(
                'Id',
                'Code',
                'Name',
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($zones)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $zones;
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

    public function update($Id, Request $request) {
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

            if (empty($Id))
                throw new Exception("Request Id Invalid");

            if (empty($request->input('Name')) || strlen($request->input('Name')) > 255)
                throw new Exception("Request Name Invalid");

            $Name = $request->input("Name");
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $affected = DB::table('MS_Zone')->where('Id', $Id)->update([
                'Name' => $Name,
                'StatusFlag' => empty($StatusFlag) ? 1 : $StatusFlag,
                'ModifUN' => $UN
            ]);

            if ($affected < 1)
                throw new Exception("Update Failed with affected < 1");

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

    public function delete($Id) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($Id))
                throw new Exception("Request Id Invalid");

            $affected = DB::table('MS_Zone')->where('Id', $Id)->update([
                'StatusFlag' => 0
            ]);

            if ($affected < 1)
                throw new Exception("Delete Failed with affected < 1");

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

    public function show($Id, $Type) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $zones = DB::table('MS_Zone')->where([
                ['Id', '=', $Id],
                ['StatusFlag', '=', 1]
            ])->select(
                'Id',
                'Code',
                'Name',
                DB::raw('null as ZoneDetail'),
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($zones)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                if ($Type == 1) {
                    $zonedetails = DB::table('MS_ZoneDetail')
                    ->join('MS_Zone', 'MS_Zone.Id', '=', 'MS_ZoneDetail.ZoneId')
                    ->join('MS_Device', 'MS_Device.Id', '=', 'MS_ZoneDetail.DeviceId')
                    ->where([
                        ['MS_ZoneDetail.ZoneId', '=', $Id],
                        ['MS_ZoneDetail.StatusFlag', '=', 1]
                    ])->select(
                        'MS_ZoneDetail.Id as ZoneDetailId',
                        'MS_ZoneDetail.ZoneId as ZoneId',
                        'MS_Zone.Name as ZoneName',
                        'MS_ZoneDetail.DeviceId as DeviceId',
                        'MS_Device.Name as DeviceName',
                        'MS_ZoneDetail.Name as Name',
                        DB::raw('CASE WHEN (SELECT count(1) FROM TR_Booking where TR_Booking.ZoneDetailId = MS_ZoneDetail.Id and TR_Booking.Status IN (0, 1)) > 0 THEN 1 ELSE 0 END as Status')
                    )->get();
                    $zones[0]->ZoneDetail = $zonedetails;
                } else {
                    $zonedetails = DB::select('SELECT a.`Id` as `ZoneDetailId`, a.`ZoneId` as `ZoneId`, b.`Name` as `ZoneName`, a.`Id` as `DeviceId`, c.`Name` as `DeviceName`, a.`Name` as `Name`, 0 as `Status` FROM `MS_ZoneDetail` a JOIN `MS_Zone` b ON b.`Id` = a.`ZoneId` JOIN `MS_Device` c ON c.`Id` = a.`DeviceId` WHERE a.`Id` not in( SELECT d.`ZoneDetailId` FROM `TR_Booking` d WHERE d.`Id` IN ( SELECT MAX(e.`Id`) FROM `TR_Booking` e GROUP BY e.`ZoneDetailId`) AND d.`Status` IN (0,1) ) AND a.`ZoneId` = ? AND a.`StatusFlag` = 1', [$Id]);
                    $zones[0]->ZoneDetail = $zonedetails;
                }
                
                $response["Data"] = $zones;
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

    public function createZoneDetail(Request $request) {
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
                
            if (empty($request->input('ZoneId')))
                throw new Exception("Request ZoneId Invalid");

            if (empty($request->input('DeviceId')))
                throw new Exception("Request DeviceId Invalid");

            if (empty($request->input('Name')) || strlen($request->input('Name')) > 255)
                throw new Exception("Request Name Invalid");

            $ZoneId = $request->input("ZoneId");
            $DeviceId = $request->input("DeviceId");
            $Name = $request->input("Name");
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $ZoneDetailExist = DB::table('MS_ZoneDetail')->where([
                ['ZoneId', '=', $ZoneId],
                ['DeviceId', '=', $DeviceId],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($ZoneDetailExist > 0)
                throw new Exception("Data Already Exist");

            DB::table('MS_ZoneDetail')->insert([[
                'ZoneId' => $ZoneId,
                'DeviceId' => $DeviceId,
                'Name' => $Name,
                'StatusFlag' => empty($StatusFlag) ? 1 : $StatusFlag,
                'InputUN' => $UN,
                'ModifUN' => $UN
            ]]);

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

    public function deleteZoneDetail(Request $request) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($request->input('Id')))
                throw new Exception("Request Id Invalid");

            $Id = $request->input('Id');

            $affected = DB::table('MS_ZoneDetail')->where('Id', $Id)->update([
                'StatusFlag' => 0
            ]);

            if ($affected < 1)
                throw new Exception("Delete Failed with affected < 1");

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
}

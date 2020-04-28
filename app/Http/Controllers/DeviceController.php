<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Exception;

class DeviceController extends Controller
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

            if ($request->input('PowerStatus') == null || $request->input('PowerStatus') == ""/* || !is_int($request->input('PowerStatus'))*/)
                throw new Exception("Request PowerStatus Invalid");

            if ($request->input('LockStatus') == null || $request->input('LockStatus') == ""/* || !is_int($request->input('LockStatus'))*/)
                throw new Exception("Request LockStatus Invalid");

            $Code = $request->input("Code");
            $Name = $request->input("Name");
            $PowerStatus = (int)$request->input("PowerStatus");
            $LockStatus = (int)$request->input("LockStatus");
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $DeviceCodeExist = DB::table('MS_Device')->where([
                ['Code', '=', $Code],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($DeviceCodeExist > 0)
                throw new Exception("Code Already Exist");

            DB::table('MS_Device')->insert([[
                'Code' => $Code,
                'Name' => $Name,
                'PowerStatus' => $PowerStatus,
                'LockStatus' => $LockStatus,
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
            $devices = DB::table('MS_Device')->where("StatusFlag", 1)->select(
                'Id',
                'Code',
                'Name',
                'PowerStatus',
                DB::raw('(CASE WHEN PowerStatus = 1 THEN \'ON\' ELSE \'OFF\' END) as PowerStatusName'),
                'LockStatus',
                DB::raw('(CASE WHEN LockStatus = 1 THEN \'OPEN\' ELSE \'CLOSE\' END) as LockStatusName'),
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($devices)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $devices;
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

            if ($request->input('PowerStatus') == null || $request->input('PowerStatus') == ""/* || !is_int($request->input('PowerStatus'))*/)
                throw new Exception("Request PowerStatus Invalid");

            if ($request->input('LockStatus') == null || $request->input('LockStatus') == ""/* || !is_int($request->input('LockStatus'))*/)
                throw new Exception("Request LockStatus Invalid");

            $Name = $request->input("Name");
            $PowerStatus = (int)$request->input("PowerStatus");
            $LockStatus = (int)$request->input("LockStatus");
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $affected = DB::table('MS_Device')->where('Id', $Id)->update([
                'Name' => $Name,
                'PowerStatus' => $PowerStatus,
                'LockStatus' => $LockStatus,
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

            $affected = DB::table('MS_Device')->where('Id', $Id)->update([
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

    public function show($Id) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $devices = DB::table('MS_Device')->where([
                ['Id', '=', $Id],
                ['StatusFlag', '=', 1]
            ])->select(
                'Id',
                'Code',
                'Name',
                'PowerStatus',
                DB::raw('(CASE WHEN PowerStatus = 1 THEN \'ON\' ELSE \'OFF\' END) as PowerStatusName'),
                'LockStatus',
                DB::raw('(CASE WHEN LockStatus = 1 THEN \'OPEN\' ELSE \'CLOSE\' END) as LockStatusName'),
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($devices)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $devices;
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

    public function readDevicewithZoneId($zoneId) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            $devices = DB::table('MS_Device')
            ->whereNotIn('Id', function($query) {
                $query->select('DeviceId')
                ->from('MS_ZoneDetail')
                ->whereRaw('MS_ZoneDetail.StatusFlag = 1');
            })
            ->select('Id', 'Code', 'Name')
            ->get();
            
            if (empty($devices)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $devices;
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
}

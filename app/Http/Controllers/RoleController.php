<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Exception;

class RoleController extends Controller
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

            $RoleCodeExist = DB::table('MS_Role')->where([
                ['Code', '=', $Code],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($RoleCodeExist > 0)
                throw new Exception("Code Already Exist");

            DB::table('MS_Role')->insert([[
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
            $roles = DB::table('MS_Role')->where("StatusFlag", 1)->select(
                'Id',
                'Code',
                'Name',
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($roles)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $roles;
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

            $affected = DB::table('MS_Role')->where('Id', $Id)->update([
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

            $affected = DB::table('MS_Role')->where('Id', $Id)->update([
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
            $roles = DB::table('MS_Role')->where([
                ['Id', '=', $Id],
                ['StatusFlag', '=', 1]
            ])->select(
                'Id',
                'Code',
                'Name',
                'StatusFlag',
                'InputUN',
                'InputTime',
                'ModifUN',
                'ModifTime'
            )->get();

            if (empty($roles)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $roles;
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

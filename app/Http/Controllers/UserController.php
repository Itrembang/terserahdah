<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;

use Exception;

class UserController extends Controller
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

            if (empty($request->input('RoleId'))/* || !is_int($request->input('RoleId'))*/)
                throw new Exception("Request RoleId Invalid");

            if (empty($request->input('Email')) || strlen($request->input('Email')) > 255)
                throw new Exception("Request Email Invalid");

            if (empty($request->input('UserName')) || strlen($request->input('UserName')) > 16)
                throw new Exception("Request UserName Invalid");

            if (empty($request->input('FullName')) || strlen($request->input('FullName')) > 255)
                throw new Exception("Request FullName Invalid");

            if (empty($request->input('Password')) || strlen($request->input('Password')) > 255)
                throw new Exception("Request Password Invalid");

            $RoleId = (int)$request->input("RoleId");
            $Email = $request->input("Email");
            $UserName = $request->input("UserName");
            $FullName = $request->input("FullName");
            $Password = md5($request->input("Password"));
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $UserNameExists = DB::table('MS_User')->where([
                ['UserName', '=', $UserName],
                ['StatusFlag', '=', 1]
            ])->count();

            if ($UserNameExists > 0)
                throw new Exception("UserName Already Exist");

            DB::table('MS_User')->insert([[
                'RoleId' => $RoleId,
                'Email' => $Email,
                'UserName' => $UserName,
                'FullName' => $FullName,
                'Password' => $Password,
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
            $users = DB::table('MS_User')
                    ->join('MS_Role', 'MS_Role.Id', '=', 'MS_User.RoleId')
                    ->where('MS_User.StatusFlag', 1)
                    ->select(
                        'MS_User.Id as Id',
                        'MS_Role.Id as RoleId',
                        'MS_Role.Code as RoleCode',
                        'MS_Role.Name as RoleName',
                        'MS_User.Email',
                        'MS_User.Username',
                        'MS_User.FullName',
                        'MS_User.StatusFlag',
                        'MS_User.InputUN',
                        'MS_User.InputTime',
                        'MS_User.ModifUN',
                        'MS_User.ModifTime'
                    )
                    ->get();

            if (empty($users)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $users;
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

            if (empty($request->input('RoleId'))/* || !is_int($request->input('RoleId'))*/)
                throw new Exception("Request RoleId Invalid");

            if (empty($request->input('Email')) || strlen($request->input('Email')) > 255)
                throw new Exception("Request Email Invalid");

            if (empty($request->input('FullName')) || strlen($request->input('FullName')) > 255)
                throw new Exception("Request FullName Invalid");

            if (empty($request->input('Password')) || strlen($request->input('Password')) > 255)
                throw new Exception("Request Password Invalid");

            $RoleId = (int)$request->input("RoleId");
            $Email = $request->input("Email");
            $FullName = $request->input("FullName");
            $Password = md5($request->input("Password"));
            $StatusFlag = $request->input("StatusFlag", 1);
            $UN = $request->input("UN", null);

            $affected = DB::table('MS_User')->where('Id', $Id)->update([
                'RoleId' => $RoleId,
                'Email' => $Email,
                'FullName' => $FullName,
                'Password' => $Password,
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

            $affected = DB::table('MS_User')->where('Id', $Id)->update([
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
            $users = DB::table('MS_User')
                    ->join('MS_Role', 'MS_Role.Id', '=', 'MS_User.RoleId')
                    ->where([
                        ['MS_User.Id', '=', $Id],
                        ['MS_User.StatusFlag', '=', 1]
                    ])
                    ->select(
                        'MS_User.Id as Id',
                        'MS_Role.Id as RoleId',
                        'MS_Role.Code as RoleCode',
                        'MS_Role.Name as RoleName',
                        'MS_User.Email',
                        'MS_User.Username',
                        'MS_User.FullName',
                        'MS_User.StatusFlag',
                        'MS_User.InputUN',
                        'MS_User.InputTime',
                        'MS_User.ModifUN',
                        'MS_User.ModifTime'
                    )
                    ->get();

            if (empty($users)) {
                $response["Message"] = "SUCCESS but Table Empty";
            } else {
                $response["Data"] = $users;
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

    public function login(Request $request) {
        $httpcode = 500;
        $response = array(
            "Code" => 1,
            "Error" => null,
            "Message" => "FAILED",
            "Data" => null
        );

        try {
            if (empty($request->input('UserName')) || strlen($request->input('UserName')) > 16)
                throw new Exception("Request UserName Invalid");

            if (empty($request->input('Password')) || strlen($request->input('Password')) > 255)
                throw new Exception("Request Password Invalid");

            $UserName = $request->input("UserName");
            $Password = md5($request->input("Password"));

            $users = DB::table('MS_User')
            ->join('MS_Role', 'MS_Role.Id', '=', 'MS_User.RoleId')
            ->where([
                ['MS_User.UserName', '=', $UserName],
                ['MS_User.Password', '=', $Password],
                ['MS_User.StatusFlag', '=', 1]
            ])
            ->select(
                'MS_User.Id as Id',
                'MS_Role.Id as RoleId',
                'MS_Role.Code as RoleCode',
                'MS_Role.Name as RoleName',
                'MS_User.Email',
                'MS_User.Username',
                'MS_User.FullName',
                'MS_User.StatusFlag',
                'MS_User.InputUN',
                'MS_User.InputTime',
                'MS_User.ModifUN',
                'MS_User.ModifTime'
            )
            ->get();
            
            if (empty($users) || count($users) < 1) {
                throw new Exception("Failed Login : UserName & Password Invalid");
            } else {
                $response["Data"] = $users;
                $response["Message"] = "Success Login";
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

<?php

class MemberController extends ApiController
{

    protected $ZetyxMemberTableModel;

    public function __construct()
    {
        parent::__construct();
        //$this->ZetyxMemberTableModel = new ZetyxMemberTable();
    }

    public function getMember($req) {

        $params = $req['params'];
        $body = $req['body'];

        return array('params' => $params, 'body' => $body);

    }

}
<?php

class FiberHome {

    private $fp;
    public $AnmServer_Port = 3337;
    private $isolate_block = '--------------------------------------------------------------------------------';

    function Connect($host, $port = 3337, $ipolt) {
        $this->OLT=$ipolt;
		$this->fp = fsockopen($host, $port, $errno, $errstr, 90); 
	   if (!$this->fp) {
            return false;
        } else {
            return true;
        }
    }

    function CMD_OK($ret) {
        if (strpos($ret[4], 'ENDESC=No error') > 0) {
            return true;
        } else {
            return false;
        }
    }

    function Login($username, $pass) {
        $ret = $this->cmd("LOGIN:::CTAG::UN=$username,PWD=$pass;");
        return $this->CMD_OK($ret);
    }

    function LogOut() {
        $ret = $this->cmd("LOGOUT:::CTAG::;");
        return $this->CMD_OK($ret);
    }

    function Handshaking() {
        $ret = $this->cmd("SHAKEHAND:::CTAG::;");
        return $this->CMD_OK($ret);
    }

    public function cmd($cmd) {
        fwrite($this->fp, "$cmd\n");
        $buffer = '';
        while (true) {
            $c = fread($this->fp, 1);
            $buffer.=$c;
            if ($c == ';')
                break;
        }
        $ret = explode("\n", $buffer);
        foreach ($ret as $id => $rt) {
            $ret[$id] = trim($rt);
        }
        return $ret;
    }

    public function ReturnRecords($list) {
//print_r($list);
        if ((strpos($list[4], 'ENDESC=Recurso não existe') > 0) or ( strpos($list[4], 'ENDESC=Parametro invalido') > 0)) {
            return $list[4];
        } else {
            $tmp = explode("=", $list[4]);
            $param['total_blocks'] = $tmp[1];
            $tmp = explode("=", $list[5]);
            $param['block_number'] = @$tmp[1];
            if ($param['total_blocks'] == '0') {
                return array();
            } else {
                for ($ii = 1; $ii <= $param['total_blocks']; $ii++) {
                    $records = array();
                    $param['start_block'] = '';
                    $param['end_block'] = '';
                    foreach ($list as $line => $rs) {
                        if ($rs == $this->isolate_block) {
                            if ($param['start_block'] == '') {
                                $param['start_block'] = $line + 2;
                                $param['end_block'] = $line + 1 + $param['block_records'];
                                $param['titles'] = explode("\t", $list[$line + 1]);
                                break;
                            }
                        } elseif (strpos($rs, 'lock_records=') == 1) {
                            $tmp = explode("=", $rs);
                            $param['block_records'] = $tmp[1];
                        }
                    }
                    for ($i = $param['start_block']; $i <= $param['end_block']; $i++) {
                        $records[] = explode("\t", $list[$i]);
                    }
                    $i+=5;
                    foreach ($list as $id => $listid) {
                        if ($id < $i) {
                            unset($list[$id]);
                        }
                    }
                    foreach ($records as $record) {
                        $tmp = array();
                        foreach ($record as $item_id => $item_value) {
                            $tmp[$param['titles'][$item_id]] = $item_value;
                        }
                        $ret[] = $tmp;
                    }
                }
                return @$ret;
            }
        }
    }

    public function OLT_Info() {
        return $this->ReturnRecords($this->cmd("LST-DEVICE::OLTID={$this->OLT}:CTAG::;"));
    }

    public function ONU_Unregistered() {
        return $this->ReturnRecords($this->cmd("LST-UNREGONU::OLTID={$this->OLT}:CTAG::;"));
    }

    public function ONU_List() {
        return $this->ReturnRecords($this->cmd("LST-ONU::OLTID={$this->OLT}:CTAG::;"));
    }
	
    public function ONU_List_PON($PONID) {
        return $this->ReturnRecords($this->cmd("LST-ONU::OLTID={$this->OLT},PONID={$PONID}:CTAG::;"));
    }

    public function ONU_List_IP($ONUIP) {
        return $this->ReturnRecords($this->cmd("LST-ONU::ONUIP={$ONUIP}:CTAG::;"));
    }

    public function OLT_Shelf() {
        return $this->ReturnRecords($this->cmd("LST-SHELF:::CTAG::;"));
    }

    public function OLT_Board() {
        return $this->ReturnRecords($this->cmd("LST-BOARD:::CTAG::;"));
    }

    public function ONU_Ping_IP($ONUIP, $IP) {
        return $this->ReturnRecords($this->cmd("PING::ONUIP={$ONUIP}:CTAG::IP={$IP};"));
    }

    public function ONU_Ping($PONID, $ONUID, $IP, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("PING::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::IP={$IP};"));
    }

    public function ONU_LanInfo($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT) {
        return $this->ReturnRecords($this->cmd("LST-ONULANINFO::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;"));
    }

    public function ONU_LanCAR($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT) {
        return $this->ReturnRecords($this->cmd("LST-LANCAR::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;"));
    }

    public function ONU_IPTV_CFG($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-IPTVCFG:::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_IPTV($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT) {
        return $this->ReturnRecords($this->cmd("LST-IPTV::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;"));
    }

    public function ONU_LAN_PORT($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_Service_Status($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-ONUSERVICESTATUS::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_LAN_PORTVLAN($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT = '') {
        if ($ONUPORT == '') {
            return $this->ReturnRecords($this->cmd("LST-PORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
        } else {
            return $this->ReturnRecords($this->cmd("LST-PORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;"));
        }
    }

    public function ONU_OpticalInfo($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-OMDDM::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_Info($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-DEVINFO::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_WANInfo($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-ONUWANSERVICECFG::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_WANMacAddr($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-PORTMACADDRESS::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},PORTID=NA-NA-NA-1:CTAG::;"));
    }

    public function ONU_Config($PONID, $ONUID, $ONUTYPE = 'MAC') {
        return $this->ReturnRecords($this->cmd("LST-ONUCFG::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::;"));
    }

    public function ONU_LAN_PORT_MACLIMIT($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $COUNT = 1) {
        return $this->ReturnRecords($this->cmd("CFG-LANPORTMACLIMIT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::COUNT={$COUNT};"));
    }

    public function ONU_SetWanService($PONID, $ONUID, $ONUTYPE = 'MAC', $STATUS = '1', $configs) {
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $list = $this->cmd("SET-WANSERVICE::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::STATUS={$STATUS}{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "SET-WANSERVICE::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::STATUS={$STATUS}{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_Add($PONID, $AUTHTYPE, $ONUID, $NAME, $ONUTYPE) {
        $ONUID = trim($ONUID);
        $list = $this->cmd("ADD-ONU::OLTID={$this->OLT},PONID={$PONID}:CTAG::AUTHTYPE={$AUTHTYPE},ONUID={$ONUID},NAME={$NAME},ONUTYPE={$ONUTYPE};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            ECHO "ADD-ONU::OLTID={$this->OLT},PONID={$PONID}:CTAG::AUTHTYPE={$AUTHTYPE},ONUID={$ONUID},NAME={$NAME},ONUTYPE={$ONUTYPE};\n";
            print_r($list);
            return false;
        }
    }
	
	
    public function ONU_ADD_WANSERVICE($PONID, $ONUID, $VLAN, $USUARIO, $SENHA ) {
        return $this->ReturnRecords($this->cmd("
		SET-WANSERVICE::OLTID={$this->OLT},
		PONID={$PONID},
		ONUIDTYPE=MAC,
		ONUID={$ONUID}:CTAG::
		STATUS=1,
		MODE=2,
		CONNTYPE=2,
		VLAN={$VLAN},
		COS=1,
		NAT=1,
		IPMODE=3,
		PPPOEPROXY=2,
		PPPOEUSER={$USUARIO},
		PPPOEPASSWD={$SENHA},
		PPPOENAME=,
		PPPOEMODE=1,
		UPORT=0,
		WANSVC=1
		;
		"));
    }
	

//FUNCAO ADD WANSERVICE

    public function ONU_LAN_Activate($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT) {
        $list = $this->cmd("ACT-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "ACT-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_DeActivate($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT) {
        $list = $this->cmd("DACT-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "DACT-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::;\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_Configure($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $configs) {
        /*
         * BW=
         * VLANMOD=
         * PVID=
         * PCOS=
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("CFG-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "CFG-LANPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_MVLAN_ADD($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $configs) {
        /*
         * UV=
         * MVLAN=
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("ADD-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "ADD-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_MVLAN_CFG($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $configs) {
        /*
         * FLMODE=
         * MAXGRP=
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("CFG-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "CFG-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_MVLAN_DEL($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $configs) {
        /*
         * UV=
         * MVLAN=
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("DEL-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "DEL-LANIPTVPORT::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_VLAN_ADD($PONID, $ONUID, $ONUIDTYPE, $ONUPORT, $configs) {
        
        $list = $this->cmd("CFG-LANPORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUIDTYPE},ONUPORT={$ONUPORT}:CTAG::{$configs};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "CFG-LANPORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_LAN_VLAN_DEL($PONID, $ONUID, $ONUTYPE = 'MAC', $ONUPORT, $configs) {

        $list = $this->cmd("DEL-LANPORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$configs};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "DEL-LANPORTVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE},ONUPORT={$ONUPORT}:CTAG::{$configs};\n";
            print_r($list);
            return false;
        }
    }
	//DEL ONU

    public function ONU_VLAN_PON_ADD($PONID, $ONUID, $ONUTYPE = 'MAC', $configs) {
        /*
         * SVLAN=
         * UV=
         * SCOS=
         * CCOS=
         * 
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("ADD-PONVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "ADD-PONVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_VLAN_PON_DEL($PONID, $ONUID, $ONUTYPE = 'MAC', $configs) {
        /*
         * UV=
         */
        $CONFIG_ = "";
        if (count($configs) > 0) {
            foreach ($configs as $id => $config) {
                $CONFIG_.=",$id=$config";
            }
        }
        $CONFIG_ = substr($CONFIG_, 1);
        $list = $this->cmd("DEL-PONVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::{$CONFIG_};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "DEL-PONVLAN::OLTID={$this->OLT},PONID={$PONID},ONUID={$ONUID},ONUIDTYPE={$ONUTYPE}:CTAG::{$CONFIG_};\n";
            print_r($list);
            return false;
        }
    }

    public function ONU_Del($PONID, $ONUID, $ONUTYPE) {
        # DEL-ONU::OLTID=olt-name,PONID=ponport_location:CTAG::ONUIDTYPE=onuid-type,ONUID=onu-index;
        $list = $this->cmd("DEL-ONU::OLTID={$this->OLT},PONID={$PONID}:CTAG::ONUID={$ONUID},ONUIDTYPE={$ONUTYPE};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            echo "DEL-ONU::OLTID={$this->OLT},PONID={$PONID}:CTAG::ONUID={$ONUID},ONUIDTYPE={$ONUTYPE};\n";
            print_r($list);
            return false;
        }
    }

    public function Compare_WANService($onu, $VALID_ONU) {
        /*
         * Comparar os seguintes itens
         * 
         * - CONNMODE
         * - CONNTYPE
         * - VLANID
         * - VLANCOS
         * - QOSFLAG
         * - NATFLAG 
         * - IPOBTAINTYPE
         * - BINDPORTNO
         */

        // CONNMODE
        if (!($VALID_ONU['CONNMODE'] == '2')) {
            return "CONNMODE";
        }
        // CONNTYPE
        if (!($VALID_ONU['CONNTYPE'] == '2')) {
            return "CONNTYPE";
        }
        // VLANID
        if (!($VALID_ONU['VLANID'] == $onu['oops_c_internet_ftth_vlan_net'])) {
            return "VLANID";
        }
        // VLANCOS
        if (!($VALID_ONU['VLANCOS'] == '1')) {
            return "VLANCOS";
        }
        // QOSFLAG
        if (!($VALID_ONU['QOSFLAG'] == '1')) {
            return "QOSFLAG";
        }
        // NATFLAG
        if (!($VALID_ONU['NATFLAG'] == '1')) {
            return "NATFLAG";
        }
        // IPOBTAINTYPE
        if (!($VALID_ONU['IPOBTAINTYPE'] == '1')) {
            return "IPOBTAINTYPE";
        }
        // BINDPORTNO
        if (!($VALID_ONU['BINDPORTNO'] == 'LAN1')) {
            return "BINDPORTNO";
        }
    }

    public function ONU_RemoveWANServices($onu, $wanservice) {
        $configs = array();
        $configs['MODE'] = $wanservice['CONNMODE'];
        $configs['CONNTYPE'] = $wanservice['CONNTYPE'];
        $configs['VLAN'] = $wanservice['VLANID'];
        $configs['COS'] = $wanservice['VLANCOS'];
        $wanservice['BINDPORTNO'] = str_replace("LAN", "", $wanservice['BINDPORTNO']);
        $wanservice['BINDPORTNO'] = str_replace("SSID", "10", $wanservice['BINDPORTNO']);
        if (strpos($wanservice['BINDPORTNO'], "-") > 0) {
            $ports = explode("-", $wanservice['BINDPORTNO']);
            foreach ($ports as $port) {
                $configs['UPORT'] = $port;
                $this->ONU_SetWanService($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 2, $configs);
            }
        } else {
            $configs['UPORT'] = $wanservice['BINDPORTNO'];
            $this->ONU_SetWanService($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 2, $configs);
        }
    }

    public function Verify_Services($onu, $tv) {
        $onu_wanServices = $this->ONU_WANInfo($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn']);
        switch ($onu['oops_t_ftth_modem_access_id']) {
            case "1":
                // WAN Service
                $add_wan_service = false;
                if (count($onu_wanServices) == 0) {
                    // Adicionando nova WAN Service
                    $add_wan_service = true;
                } elseif (count($onu_wanServices) == 1) {
                    $wanservice = $onu_wanServices[0];
                    $compared = $this->Compare_WANService($onu, $wanservice);
                    if ($compared) {
                        echo $onu['oops_c_internet_ftth_sn'] . " - MODIFICA WAN_SERVICE ($compared)\n";
                        $this->ONU_RemoveWANServices($onu, $wanservice);
                        $add_wan_service = true;
                    }
                } else {
                    //Remove WAN_Services
                    echo $onu['oops_c_internet_ftth_sn'] . " - VARIAS WAN_SERVICE removendo todas\n";
                    foreach ($onu_wanServices as $wanservice) {
                        $this->ONU_RemoveWANServices($onu, $wanservice);
                    }
                    $add_wan_service = true;
                }

                if ($add_wan_service) {
                    $WAN_SETTINGS = array(
                        'MODE' => 2,
                        'CONNTYPE' => 2,
                        'VLAN' => $onu['oops_c_internet_ftth_vlan_net'],
                        'COS' => 1,
                        'QOS' => 1,
                        'NAT' => 1,
                        'IPMODE' => 1,
                        'UPORT' => 1,
                    );
                    echo $onu['oops_c_internet_ftth_sn'] . " - ADD WAN_SERVICE\n";
                    $this->ONU_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC');
                    $ret = $this->ONU_ADD($onu['oops_t_cmts_portas_interface'], 'MAC', $onu['oops_c_internet_ftth_sn'], "Oops_" . $onu['oops_c_internet_id'], $onu['oops_t_ftth_modem_type']);
                    if ($ret == true) {
                        $this->ONU_SetWanService($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 1, $WAN_SETTINGS);
                    }
                }

                // Remove Service_LAN
                $services = $this->ONU_LAN_PORTVLAN($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1');
                if (count($services) > 0) {
                    foreach ($services as $service) {
                        if (!($service['CVLAN'] == $onu['oops_c_internet_ftth_vlan_net'])) {
                            $this->ONU_LAN_VLAN_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1', array('CVLAN' => $service['CVLAN']));
                        }
                    }
                }
                break;
            case "2":
                // Bridge
                //Remove WAN_Services
                if (count($onu_wanServices) > 0) {
                    foreach ($onu_wanServices as $wanservice) {
                        $this->ONU_RemoveWANServices($onu, $wanservice);
                    }
                }
                $add_service_lan = false;
                // ADD PORT SERVICES
                $services = $this->ONU_LAN_PORTVLAN($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1');
                if (count($services) > 0) {
                    foreach ($services as $service) {
                        if (!($service['CVLAN'] == $onu['oops_c_internet_ftth_vlan_net'])) {
                            echo $onu['oops_c_internet_ftth_sn'] . " - DEL SERVICE LAN\n";
                            $this->ONU_LAN_VLAN_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1', array('CVLAN' => $service['CVLAN']));
                            $add_service_lan = true;
                        }
                    }
                } else {
                    $add_service_lan = true;
                }

                if ($add_service_lan) {
                    echo $onu['oops_c_internet_ftth_sn'] . " - ADD SERVICE LAN\n";
                    $this->ONU_LAN_Configure($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1', array('VLANMOD' => 'Tag', 'PVID' => $onu['oops_c_internet_ftth_vlan_net'], 'PCOS' => 0));
                }
                break;
        }

        if (Verifica_Bloqueio($onu) == false) {
            $this->ONU_LAN_Activate($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-1');
        } else {
            $lans = $this->ONU_LAN_PORT($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC');
            foreach ($lans as $lan) {
                $this->ONU_LAN_DeActivate($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', $lan['PORTID']);
            }
        }
        /*
         * Configura MULTICAST_SERVICE
         */
        $lans = $this->ONU_LAN_PORT($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC');
        unset($lans[0]);

        if (count($tv) > 0) {
            foreach ($tv as $stb) {
                $real_lan = Locate_ONU_Lan($stb, $lans);
                if ($real_lan) {

                    $this->ONU_LAN_Activate($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta']);
                    /*
                     * Verifica Multicast VLAN
                     */
                    $multicasts = $this->ONU_IPTV($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta']);
                    $add_multicast = true;

                    if (count($multicasts) > 0) {
                        foreach ($multicasts as $multicast) {
                            if ($multicast['MVLAN'] == $stb['oops_c_internet_ftth_vlan_tv']) {
                                $add_multicast = false;
                            } else {
                                $this->ONU_LAN_MVLAN_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta'], array('MVLAN' => $multicast['MVLAN']));
                            }
                        }
                    }

                    if ($add_multicast) {
                        $this->ONU_LAN_Configure($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta'], array('VLANMOD' => 'Tag', 'PVID' => $onu['oops_c_internet_ftth_vlan_tv'], 'PCOS' => 0));
                        $this->ONU_LAN_MVLAN_ADD($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta'], array('MVLAN' => $stb['oops_c_internet_ftth_vlan_tv']));
                    }
                    /*
                     * Verifica Unicast VLAN (for IPTV)
                     */
                    $services = $this->ONU_LAN_PORTVLAN($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta']);
                    if (count($services) > 0) {
                        foreach ($services as $service) {
                            if (!($service['CVLAN'] == $onu['oops_c_internet_ftth_vlan_tv'])) {
                                $this->ONU_LAN_VLAN_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta'], array('CVLAN' => $service['CVLAN']));
                                $add_service_lan = true;
                            }
                        }
                    } elseif (count($services) <> 2) {
                        $add_service_lan = true;
                    }

                    if ($add_service_lan) {
                        $this->ONU_LAN_Configure($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', 'NA-NA-NA-' . $stb['oops_c_internet_porta'], array('VLANMOD' => 'Tag', 'PVID' => $onu['oops_c_internet_ftth_vlan_tv'], 'PCOS' => 0));
                    }

                    foreach ($lans as $id_ => $lan) {
                        if ($lan == $real_lan) {
                            unset($lans[$id_]);
                        }
                    }
                }
            }
        }
        /*
         * Desativa LANS sem uso
         */
        if (count($lans) > 0) {
            foreach ($lans as $lan) {
                $services = $this->ONU_LAN_PORTVLAN($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', $lan['PORTID']);
                if (count($services) > 0) {
                    foreach ($services as $service) {
                        $this->ONU_LAN_VLAN_DEL($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', $lan['PORTID'], array('CVLAN' => $service['CVLAN']));
                    }
                }
                $this->ONU_LAN_DeActivate($onu['oops_t_cmts_portas_interface'], $onu['oops_c_internet_ftth_sn'], 'MAC', $lan['PORTID']);
            }
        }
    }

    public function ONU_Compare($ONU, $VALID_ONU) {
        /*
         * Comparar os seguintes itens
         * 
         * - PONID
         * - NAME
         * - ONUTYPE
         * - AUTHTYPE
         * - MAC
         */

        // PONID
        if (!($VALID_ONU['PONID'] == $ONU['oops_t_cmts_portas_interface'])) {
            return "PONID";
        }

        // NAME
        if (!($VALID_ONU['NAME'] == "Oops_" . $ONU['oops_c_internet_id'])) {
            return "NAME";
        }

        // ONUTYPE
        if (!($VALID_ONU['ONUTYPE'] == $ONU['oops_t_ftth_modem_type'])) {
            return "ONUTYPE";
        }

        // AUTHTYPE
        if (!($VALID_ONU['AUTHTYPE'] == 'MAC')) {
            return "AUTHTYPE";
        }

        // MAC
        if (!(strtolower($VALID_ONU['MAC']) == trim(strtolower($ONU['oops_c_internet_ftth_sn'])))) {
            return "MAC";
        }
    }

    public function ONU_Bw($PONID, $ONUID, $ONUTYPE = 'MAC', $UP = '5120', $DOWN = '5120') {
        $list = $this->cmd("CFG-ONUBW::OLTID={$this->OLT},PONID={$PONID},ONUIDTYPE={$ONUTYPE},ONUID={$ONUID}:CTAG::UPBW={$UP},DOWNBW={$DOWN};");
        if ($this->CMD_OK($list)) {
            return true;
        } else {
            print_r($list);
            return false;
        }
    }

}
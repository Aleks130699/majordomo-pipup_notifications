<?php
/**
 * pipup Notifications
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 21:12:43 [Dec 27, 2020])
 */
//
//
class pipup_notifications extends module
{
    /**
     * pipup_notifications
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "pipup_notifications";
        $this->title = "PIPup Notifications";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'pipup_devices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_pipup_devices') {
                $this->search_pipup_devices($out);
            }
            if ($this->view_mode == 'edit_pipup_devices') {
                $this->edit_pipup_devices($out, $this->id);
            }
            if ($this->view_mode == 'delete_pipup_devices') {
                $this->delete_pipup_devices($this->id);
                $this->redirect("?");
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * pipup_devices search
     *
     * @access public
     */
    function search_pipup_devices(&$out)
    {
        require(dirname(__FILE__) . '/pipup_devices_search.inc.php');
    }

    /**
     * pipup_devices edit/add
     *
     * @access public
     */
    function edit_pipup_devices(&$out, $id)
    {
        require(dirname(__FILE__) . '/pipup_devices_edit.inc.php');
    }

    function api_send_message($id, $message)
    {

        $rec=SQLSelectOne("SELECT * FROM pipup_devices WHERE ID=".(int)$id);
        $ip = $rec['IP'];

        if (!$ip) return;

        $payload = array(
            "duration" => "5",
			"position" => "0",
			"title" => "MajorDoMo",
			"titleColor" => "#0066cc",
			"titleSize" => "20",
            "message" => $message,
			"messageColor" => "#000000",
			"messageSize" => "14",
            "backgroundColor" => "#ffffff",
        );

        if ($rec['MSG_DURATION']) $payload['duration']=(string)$rec['MSG_DURATION'];
		if ($rec['MSG_POSITION']) $payload['position']=(string)$rec['MSG_POSITION'];
		if ($rec['MSG_TITLE']) $payload['title']=processTitle($rec['MSG_TITLE']);
		if ($rec['MSG_TITLECOLOR']) $payload['titleColor']=processTitle($rec['MSG_TITLECOLOR']);
		if ($rec['MSG_TITLESIZE']) $payload['titleSize']=processTitle($rec['MSG_TITLESIZE']);
		if ($rec['MSG_COLOR']) $payload['messageColor']=processTitle($rec['MSG_COLOR']);
		if ($rec['MSG_SIZE']) $payload['messageSize']=processTitle($rec['MSG_SIZE']);
        if ($rec['MSG_BKGCOLOR']) $payload['backgroundColor']=(string)$rec['MSG_BKGCOLOR'];

        $url = 'http://' . $ip . ':7979/notify';

        foreach($payload as $k=>$v) {
            $url.='&'.$k.'='.urlencode($v);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * pipup_devices delete record
     *
     * @access public
     */
    function delete_pipup_devices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM pipup_devices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM pipup_devices WHERE ID='" . $rec['ID'] . "'");
    }

    function processSubscription($event, $details = '')
    {
        if ($event == 'SAY') {
            $level = $details['level'];
            $message = $details['message'];

            $devices = SQLSelect("SELECT * FROM pipup_devices");
            $total = count($devices);
            for($i=0;$i<$total;$i++) {
                $min_level = (int)processTitle($devices[$i]['MIN_MSG_LEVEL']);
                if ($level>=$min_level) {
                    $this->api_send_message($devices[$i]['ID'],$message);
                }
            }
        }
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        subscribeToEvent($this->name, 'SAY');
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS pipup_devices');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        pipup_devices -
        */
        $data = <<<EOD
 pipup_devices: ID int(10) unsigned NOT NULL auto_increment
 pipup_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 pipup_devices: IP varchar(255) NOT NULL DEFAULT ''
 pipup_devices: MIN_MSG_LEVEL varchar(255) NOT NULL DEFAULT ''
 pipup_devices: MSG_TITLE varchar(255) NOT NULL DEFAULT ''
 pipup_devices: MSG_TITLECOLOR varchar(20) NOT NULL DEFAULT '#607d8b'
 pipup_devices: MSG_TITLESIZE int(3) NOT NULL DEFAULT '20'
 pipup_devices: MSG_COLOR varchar(20) NOT NULL DEFAULT '#607d8b'
 pipup_devices: MSG_SIZE int(3) NOT NULL DEFAULT '20'
 pipup_devices: MSG_DURATION int(3) NOT NULL DEFAULT '5'
 pipup_devices: MSG_BKGCOLOR varchar(20) NOT NULL DEFAULT '#607d8b'
 pipup_devices: MSG_TRANSPARENCY int(3) NOT NULL DEFAULT '3'
 pipup_devices: MSG_POSITION int(3) NOT NULL DEFAULT '0'
 
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRGVjIDI3LCAyMDIwIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/

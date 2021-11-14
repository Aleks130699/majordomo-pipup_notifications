<?php
/*
* @version 0.1 (wizard)
*/
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}
$table_name = 'pipup_devices';
$rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

if ($this->mode =='send_test') {
    $this->api_send_message($rec['ID'],gr('msg'));
}

if ($this->mode == 'update') {
    $ok = 1;
    //updating '<%LANG_TITLE%>' (varchar, required)
    $rec['TITLE'] = gr('title');
    if ($rec['TITLE'] == '') {
        $out['ERR_TITLE'] = 1;
        $ok = 0;
    }
    //updating 'IP' (varchar)
    $rec['IP'] = gr('ip');
    //updating 'MIN_MSG_LEVEL' (varchar)
    $rec['MIN_MSG_LEVEL'] = gr('min_msg_level');

    if ($rec['ID']) {
        $rec['MSG_DURATION']=gr('msg_duration','int');
		$rec['MSG_POSITION']=gr('msg_position','int');
		$rec['MSG_TITLE']=gr('msg_title','trim');
        $rec['MSG_TITLECOLOR']=gr('msg_titlecolor');
        $rec['MSG_TITLESIZE']=gr('msg_titlesize','int');
		$rec['MSG_COLOR']=gr('msg_color');
        $rec['MSG_SIZE']=gr('msg_size','int');
        $rec['MSG_BKGCOLOR']=gr('msg_bkgcolor');
        $rec['MSG_TRANSPARENCY']=gr('msg_transparency');
        
    }

    //UPDATING RECORD
    if ($ok) {
        if ($rec['ID']) {
            SQLUpdate($table_name, $rec); // update
        } else {
            $new_rec = 1;
            $rec['ID'] = SQLInsert($table_name, $rec); // adding new record
            $this->redirect("?view_mode=".$this->view_mode."&id=".$rec['ID']);
        }
        $out['OK'] = 1;
        $this->api_send_message($rec['ID'],'Test message '."\n".date('Y-m-d H:i:s'));
    } else {
        $out['ERR'] = 1;
    }
}
if (is_array($rec)) {
    foreach ($rec as $k => $v) {
        if (!is_array($v)) {
            $rec[$k] = htmlspecialchars($v);
        }
    }
}
outHash($rec, $out);

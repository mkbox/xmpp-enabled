<?php
/*
Plugin Name: XMPP Enabled
Plugin URI: http://sandfox.org/projects/xmpp-enabled.html
Description: A simple library plugin for XMPP notifications
Version: 0.3.2.02
Author: Sand Fox
Author URI: http://sandfox.im/

  Copyright 2010 Anton Smirnov

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

        http://www.gnu.org/licenses/gpl-2.0.html

*/

require_once(ABSPATH.'/wp-content/plugins/xmpp-enabled/XMPPHP/XMPP.php');

$xmpp_object = null;

class xmpp_class
{
    public $conn;
    public $connected;

    function __construct()
    {
        global $xmpp_connection_established;

        $jid       = get_option('xmpp_default_jid');
        $jid_elems = explode('@', $jid);
        $username  = $jid_elems[0];
        $server    = $jid_elems[1];
        $password  = get_option('xmpp_default_password');
        $resource  = get_option('xmpp_default_resource');
        $host      = get_option('xmpp_default_host');
        $port      = get_option('xmpp_default_port');
        $encryption = get_option('xmpp_enable_encryption', true);

        if(empty($jid))
        {
            xmpp_log('JID field is empty');
            return false;
        }

        if(empty($jid))
        {
            xmpp_log('Password field is empty');
            return false;
        }

        if(empty($port))
        {
            $port = '5222';
        }

        if(empty($host))
        {
            $host = $server;
        }

        if(empty($resource))
        {
            $resource = 'WordPress';
        }

        $error = false;

        try
        {
            $this->conn = new XMPPHP_XMPP($host, $port, $username, $password, $resource, $server, false, 4);

            if(!$encryption)
            {
                $this->conn->useEncryption(false);
            }

            $this->conn->connect();
            if($this->conn->isDisconnected())
            {
                throw new Exception('Connection is not started');
            }
            $this->conn->processUntil('session_start');
        }
        catch(Exception $e)
        {
            $error = $e->getMessage();
            $logs = Array();

            if(isset($conn))
            {
                $logs = $this->conn->getLog()->getLogData();
            }

            $logs[] = $error;
            xmpp_log($logs);

            $this->connected = false;
        }

        $this->connected = true;
    }

    function __destruct()
    {
        $this->conn->disconnect();
    }
}

function xmpp_send($recipient, $text, $subject='', $msg_type='normal')
{
    global $xmpp_object;

    if(!$xmpp_object)
    {
        $xmpp_object = new xmpp_class();
    }

    if(!$xmpp_object->connected)
    {
        return false;
    }

    if(($msg_type != 'chat') && ($msg_type != 'headline'))
    {
        $msg_type = 'normal';
    }

    $error = false;

    try
    {
        $xmpp_object->conn->message($recipient, $text, $msg_type, $subject);
    }
    catch(Exception $e)
    {
        $error = $e->getMessage();
        $logs = Array();

        if(isset($conn))
        {
            $logs = $conn->getLog()->getLogData();
        }

        $logs[] = $error;
        xmpp_log($logs);
    }

    return !($error);
}

function xmpp_log($msg = 'Someone forgot to enter the log message :)')
{
    $log = get_option('xmpp_log', array());

    $new_log = Array();

    if(is_array($msg))
    {
        $msgs = array_reverse($msg);
        foreach($msgs as $msg)
        {
            $new_log []= date('r') . ' ' . $msg;
        }
    }
    else
    {
        $new_log []= date('r') . ' ' . $msg;
    }

    $i = 9; // {number of log messages to store} - 1

    foreach($log as $item)
    {
        $new_log []= $item;

        if(!--$i)
        {
            break;
        }
    }

    update_option('xmpp_log', $new_log);
}

function xmpp_test()
{
    $jid = get_option('xmpp_default_jid');

    return xmpp_send($jid, 'XMPP Enabled works!', 'XMPP Enabled Test');
}

/* ----- settings section -------- */

add_action('admin_menu', 'xmpp_create_menu', 0);

function xmpp_create_menu()
{
    add_menu_page('XMPP Enabled Settings', 'XMPP Enabled', 'administrator', 'xmpp-enabled', 'xmpp_settings_page');
//    add_submenu_page('xmpp-enabled', 'XMPP Enabled Settings', 'XMPP Enabled', 'administrator', __FILE__, 'xmpp_settings_page');
    add_action('admin_init', 'register_xmpp_settings');
}


function register_xmpp_settings()
{
    register_setting('xmpp-defaults', 'xmpp_default_jid');
    register_setting('xmpp-defaults', 'xmpp_default_password');
    register_setting('xmpp-defaults', 'xmpp_default_resource');
    register_setting('xmpp-defaults', 'xmpp_default_host');
    register_setting('xmpp-defaults', 'xmpp_default_port');
    register_setting('xmpp-defaults', 'xmpp_enable_encryption');
    register_setting('xmpp-defaults', 'xmpp_send_test_msg');

    register_setting('xmpp-test', 'xmpp_send_test_msg');
}

function xmpp_settings_page() {
    ?>
    <div class="wrap">
    <h2>XMPP Enabled Settings</h2>

    <form method="post" action="options.php">
        <?php settings_fields('xmpp-defaults'); ?>
        <table class="form-table">
            <tr><th colspan="2">Default XMPP account settings:</th></tr>
            <tr valign="top">
                <th scope="row">JID</th>
                <td><input type="text" name="xmpp_default_jid" value="<?php echo get_option('xmpp_default_jid'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Password</th>
                <td><input type="password" name="xmpp_default_password" value="<?php echo get_option('xmpp_default_password'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Resource name</th>
                <td><input type="text" name="xmpp_default_resource" value="<?php echo get_option('xmpp_default_resource'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Server hostname<br/><small>leave blank unless it differs from jid hostname</small></th>
                <td><input type="text" name="xmpp_default_host" value="<?php echo get_option('xmpp_default_host'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">Server port<br/><small>leave blank unless it is not 5222</small></th>
                <td><input type="text" name="xmpp_default_port" value="<?php echo get_option('xmpp_default_port'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="xmpp_enable_encryption" id="xmpp_enable_encryption"
                        <?php if(get_option('xmpp_enable_encryption', true)) echo 'checked="checked"' ?>
                    /> <label for="xmpp_enable_encryption">Enable encryption</label>
                </th>
            </tr>

            <tr valign="top">
                <th scope="row" colspan="2">
                    <input type="checkbox" checked="checked" value="1" name="xmpp_send_test_msg" id="xmpp_send_test_msg"/>
                    <label for="xmpp_send_test_msg">Also test the connection</label></th>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>

    </form>

    <h3>Send Test Message</h3>

    <form method="post" action="options.php">
    <?php settings_fields('xmpp-test'); ?>
    <input type="hidden" name="xmpp_send_test_msg" value="1" />

    <?php

        if(get_option('xmpp_send_test_msg'))
        {
            update_option('xmpp_send_test_msg', false);
            $test_submit = 'Send another';
            if(xmpp_test())
            {
                echo '<p style="color: green">Message has been sent successfully</p>';
                xmpp_log('Test successful');
            }
            else
            {
                echo '<p style="color: red">Error on sending message</p>';
                xmpp_log('Test failed');
            }
        }
        else
        {
            echo '<p>You can send a message to yourself to test the settings above</p>';
            $test_submit = 'Send';
        }

    ?>

    <input type="submit" class="button-primary" value="<?php echo $test_submit ?>" />

    </form>

    <h3>XMPP Log</h3>
    <pre><?php
        foreach(
            array_reverse(
                get_option('xmpp_log', array()))
            as $log)
        {
            echo $log;
            echo "\n";
        }
    ?></pre>

    </div>
    <?php
} ?>

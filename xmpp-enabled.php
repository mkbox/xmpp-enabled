<?php
/**
 * Plugin Name: XMPP Enabled
 * Plugin URI: https://github.com/sandfox-im/xmpp-enabled
 * Description: A simple library plugin for XMPP notifications
 * Version: 1.0.1
 * Author: Sand Fox
 * Author URI: http://sandfox.org/
 * Text Domain: xmpp-enabled
 * Domain Path: /languages
 */
/**
 * Copyright 2010, Anton Smirnov
 * Copyright 2013, XMPP Enabled contributors
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 *      http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once(dirname(__FILE__) . '/XMPPHP/XMPP.php');

load_plugin_textdomain('xmpp-enabled', false, basename(dirname(__FILE__)) . '/languages');

class XMPPEnabled
{
    private static $instance = null;

    private $conn;
    private $connected;

    public static function instance()
    {
        return self::$instance ?: self::$instance = new self();
    }

    private function __construct()
    {
        $jid       = get_option('xmpp_default_jid');
        $jid_elems = explode('@', $jid);
        $username  = $jid_elems[0];
        $server    = $jid_elems[1];
        $password  = get_option('xmpp_default_password');
        $resource  = get_option('xmpp_default_resource');
        $host      = get_option('xmpp_default_host');
        $port      = get_option('xmpp_default_port');
        $encryption = get_option('xmpp_enable_encryption', true);

        if (empty($jid))
        {
            xmpp_log(__('JID field is empty', 'xmpp-enabled'));
            return;
        }

        if (empty($password))
        {
            xmpp_log(__('Password field is empty', 'xmpp-enabled'));
            return;
        }

        if (empty($port))
        {
            $port = '5222';
        }

        if (empty($host))
        {
            $host = $server;
        }

        if (empty($resource))
        {
            $resource = 'WordPress';
        }

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
                throw new Exception(__('Connection is not started', 'xmpp-enabled'));
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

    /**
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * @return \XMPPHP_XMPP
     */
    public function conn()
    {
        return $this->conn;
    }
}

function xmpp_send($recipient, $text, $subject='', $msg_type='normal')
{
    $xmpp_object = XMPPEnabled::instance();

    if (!$xmpp_object->isConnected())
    {
        return false;
    }

    if (($msg_type != 'chat') && ($msg_type != 'headline'))
    {
        $msg_type = 'normal';
    }

    $error = false;

    try
    {
        $xmpp_object->conn()->message($recipient, $text, $msg_type, $subject);
    }
    catch (Exception $e)
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

    return xmpp_send($jid, __('XMPP Enabled works!', 'xmpp-enabled'), __('XMPP Enabled Test', 'xmpp-enabled'));
}

/* ----- settings section -------- */

add_action('admin_menu', 'xmpp_create_menu', 0);

function xmpp_create_menu()
{
    add_menu_page('XMPP Enabled Settings', 'XMPP Enabled', 'administrator', 'xmpp-enabled', 'xmpp_settings_page');
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
    <h2><?php _e('XMPP Enabled Settings', 'xmpp-enabled') ?></h2>

    <form method="post" action="options.php">
        <?php settings_fields('xmpp-defaults'); ?>
        <table class="form-table">
            <tr><th colspan="2"><?php _e('Default XMPP account settings:', 'xmpp-enabled') ?></th></tr>
            <tr valign="top">
                <th scope="row"><?php _e('JID', 'xmpp-enabled') ?></th>
                <td><input type="text" name="xmpp_default_jid" value="<?php echo get_option('xmpp_default_jid'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Password', 'xmpp-enabled') ?></th>
                <td><input type="password" name="xmpp_default_password" value="<?php echo get_option('xmpp_default_password'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Resource name', 'xmpp-enabled') ?></th>
                <td><input type="text" name="xmpp_default_resource" value="<?php echo get_option('xmpp_default_resource'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Server hostname', 'xmpp-enabled') ?><br/><small><?php _e('leave blank unless it differs from jid hostname', 'xmpp-enabled') ?></small></th>
                <td><input type="text" name="xmpp_default_host" value="<?php echo get_option('xmpp_default_host'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Server port', 'xmpp-enabled') ?><br/><small><?php _e('leave blank unless it is not 5222', 'xmpp-enabled') ?></small></th>
                <td><input type="text" name="xmpp_default_port" value="<?php echo get_option('xmpp_default_port'); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="xmpp_enable_encryption" id="xmpp_enable_encryption"
                        <?php if(get_option('xmpp_enable_encryption', true)) echo 'checked="checked"' ?>
                    /> <label for="xmpp_enable_encryption"><?php _e('Enable encryption', 'xmpp-enabled') ?></label>
                </th>
            </tr>

            <tr valign="top">
                <th scope="row" colspan="2">
                    <input type="checkbox" checked="checked" value="1" name="xmpp_send_test_msg" id="xmpp_send_test_msg"/>
                    <label for="xmpp_send_test_msg"><?php _e('Also test the connection', 'xmpp-enabled') ?></label></th>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'xmpp-enabled') ?>" />
        </p>

    </form>

    <h3><?php _e('Send Test Message', 'xmpp-enabled') ?></h3>

    <form method="post" action="options.php">
    <?php settings_fields('xmpp-test'); ?>
    <input type="hidden" name="xmpp_send_test_msg" value="1" />

    <?php

        if(get_option('xmpp_send_test_msg'))
        {
            update_option('xmpp_send_test_msg', false);
            $test_submit = __('Send another', 'xmpp-enabled');
            if(xmpp_test())
            {
                echo '<p style="color: green">' . __('Message has been sent successfully', 'xmpp-enabled') . '</p>';
                xmpp_log(__('Test successful', 'xmpp-enabled'));
            }
            else
            {
                echo '<p style="color: red">' . __('Error on sending message', 'xmpp-enabled') . '</p>';
                xmpp_log(__('Test failed', 'xmpp-enabled'));
            }
        }
        else
        {
            echo '<p>' . __('You can send a message to yourself to test the settings above', 'xmpp-enabled') . '</p>';
            $test_submit = __('Send', 'xmpp-enabled');
        }

    ?>

    <input type="submit" class="button-primary" value="<?php echo $test_submit ?>" />

    </form>

    <h3><?php _e('XMPP Log', 'xmpp-enabled') ?></h3>
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
}

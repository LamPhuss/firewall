#!/usr/local/bin/php
<?php

/*
 * Copyright (C) 2025 BKCSense
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

require_once("config.inc");
require_once("util.inc");

/**
 * Get shell language mode from config XML
 * @return string "vi" or "en" (default: "en")
 */
function get_shell_lang_mode()
{
    global $config;
    
    if (isset($config['system']['shell_lang_mode']) && 
        in_array($config['system']['shell_lang_mode'], ['vi', 'en'])) {
        return $config['system']['shell_lang_mode'];
    }
    
    // Default to "en" if not set or invalid
    return 'en';
}

/**
 * Set shell language mode in config XML
 * @param string $lang_mode "vi" or "en"
 * @return bool true on success, false on failure
 */
function set_shell_lang_mode($lang_mode)
{
    global $config;
    
    if (!in_array($lang_mode, ['vi', 'en'])) {
        return false;
    }
    
    $config['system']['shell_lang_mode'] = $lang_mode;
    write_config('Shell language mode changed from console');
    
    return true;
}

// Command line interface
if (php_sapi_name() === 'cli') {
    if (isset($argv[1])) {
        $action = $argv[1];
        
        switch ($action) {
            case 'get':
                echo get_shell_lang_mode() . "\n";
                exit(0);
                break;
                
            case 'set':
                if (!isset($argv[2])) {
                    echo "Usage: {$argv[0]} set <vi|en>\n";
                    exit(1);
                }
                
                $lang_mode = $argv[2];
                if (set_shell_lang_mode($lang_mode)) {
                    echo "Language mode set to: {$lang_mode}\n";
                    exit(0);
                } else {
                    echo "Error: Invalid language mode. Use 'vi' or 'en'.\n";
                    exit(1);
                }
                break;
                
            case 'toggle':
                $current = get_shell_lang_mode();
                $new = ($current === 'vi') ? 'en' : 'vi';
                
                if (set_shell_lang_mode($new)) {
                    echo $new . "\n";
                    exit(0);
                } else {
                    echo "Error: Failed to toggle language mode.\n";
                    exit(1);
                }
                break;
                
            default:
                echo "Usage: {$argv[0]} <get|set|toggle> [lang_mode]\n";
                exit(1);
        }
    } else {
        echo "Usage: {$argv[0]} <get|set|toggle> [lang_mode]\n";
        exit(1);
    }
}


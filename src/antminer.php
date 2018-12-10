<?php

class Antminer {

  public static $ip;
  public static $pw;
  public static $type;

  public static $state;

  public static $config;
  public static $network;
  public static $uptime;
  public static $load;

  public static $summary;
  public static $pools;
  public static $stats;

  function getIp() {
    return self::$ip;
  }

  function getPw() {
    return self::$pw;
  }

  function getType() {
    return self::$type;
  }

  function getState() {
    return self::$state;
  }

  function getSummary() {
    return self::$summary;
  }

  function getPools() {
    return self::$pools;
  }

  function getStats() {
    return self::$stats;
  }

  function getConfig() {
    return self::$config;
  }

  function getNetwork() {
    return self::$network;
  }

  function getUptime() {
    return self::$uptime;
  }

  function getLoad() {
    return self::$load;
  }

  // Fetches state of miner
  // ONLINE = miner should be in a running state
  // OFFLINE = miner is not accessible
  // IDLE = miner has been "shutdown" so that it will not connect to mining pool and will consume minimal power
  function fetchAntminerState() {

    switch (self::$type) {
      case 'S9' :
        self::$state = self::stateBmminer();
      break;

      case 'S9i' :
        self::$state = self::stateBmminer();
      break;

      case 'D3' :
        self::$state = self::stateCgminer();
      break;

      case 'A3' :
        self::$state = self::stateCgminer();
      break;

      case 'L3' :
        self::$state = self::stateCgminer();
      break;

      default:
        self::$state = null;
      break;
    }

  }

  // Fetches Antminer /config files (bmminer.conf -or- cgminer.conf AND network.conf)
  function fetchAntminerConfig() {

    switch (self::$type) {
      case 'S9' :
        self::configBmminer();
      break;

      case 'S9i' :
        self::configBmminer();
      break;

      case 'D3' :
        self::configCgminer();
      break;

      case 'A3' :
        self::configCgminer();
      break;

      case 'L3' :
        self::configCgminer();
      break;

      default:

      break;
    }

  }

  // Update Antminer /config files (bmminer.conf -or- cgminer.conf)
  function updateAntminerConfig($config) {

    switch (self::$type) {
      case 'S9' :
        self::updateConfigBmminer($config);
      break;

      case 'S9i' :
        self::updateConfigBmminer($config);
      break;

      case 'D3' :
        self::updateConfigCgminer($config);
      break;

      case 'A3' :
        self::updateConfigCgminer($config);
      break;

      case 'L3' :
        self::updateConfigCgminer($config);
      break;

      default:

      break;
    }

  }

  // Update Antminer /config files (bmminer.conf -or- cgminer.conf)
  function updateAntminerNetwork($network) {

    switch (self::$type) {
      case 'S9' :
        self::updateNetworkBmminer($network);
      break;

      case 'S9i' :
        self::updateNetworkBmminer($network);
      break;

      case 'D3' :
        self::updateNetworkCgminer($network);
      break;

      case 'A3' :
        self::updateNetworkCgminer($network);
      break;

      case 'L3' :
        self::updateNetworkCgminer($network);
      break;

      default:

      break;
    }

  }

  // Powers miner off - requires miner to be fully powered off / on to bring back online
  function poweroff() {
    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "/sbin/poweroff";
    return self::sshExec($ip, $pw, $command);
  }

  // Updates miner config / software info to move from ONLINE to IDLE state
  function shutdown() {
    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    if ($type == "S9" || $type == "S9i") {
      return self::shutdownBmminer();
    }

    if ($type == "D3" || $type == "A3" || $type == "L3") {
      return self::shutdownCgminer();
    }
  }

  // Updates miner config / software info to move from IDLE to ONLINE state
  function startup() {
    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    if ($type == "S9" || $type == "S9i") {
      return self::startupBmminer();
    }

    if ($type == "D3" || $type == "A3" || $type == "L3") {
      return self::startupCgminer();
    }
  }

  // Reboots miner
  function reboot() {
    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "/sbin/reboot";
    return self::sshExec($ip, $pw, $command);
  }

  // Used to fetch state for Antminers that use Bmminer (S9, etc)
  private function stateBmminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    if (self::minerDirectoryExists('/config'))  {

      if (self::minerFileExists('/config/bmminer.conf_shutdown')) {
        return "IDLE";
      } else {
        return "ONLINE";
      }

    } else {
      return "OFFLINE";
    }

  }

 // Used to fetch state for Antminers that use Bmminer (S9, etc)
  private function configBmminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "more /config/bmminer.conf";
    $miner_config = json_decode(self::sshExec($ip, $pw, $command), TRUE);
    self::$config = $miner_config;

    $command = "more /config/network.conf";
    $network_string = self::sshExec($ip, $pw, $command);

    $network_string_lines = explode(PHP_EOL, $network_string);

    $network_config = [];
    foreach ($network_string_lines as $k=>$v) {
      $keyval = explode("=",$v);

      switch ($keyval[0]) {
         case 'hostname':
             $network_config['hostname'] = $keyval[1];
             break;

         case 'ipaddress':
             $network_config['ipaddress'] = $keyval[1];
             break;

         case 'netmask':
             $network_config['netmask'] = $keyval[1];
             break;

         case 'gateway':
             $network_config['gateway'] = $keyval[1];
             break;

         case 'dnsservers':
             $network_config['dnsservers'] = str_replace('"','',$keyval[1]);
             break;
       }

    }
   self::$network = $network_config;

  }

  // used to update config for Antminers that use Bmminer (S9, etc)
  private function updateConfigBmminer($config_json) {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $config_json_string = json_encode($config_json);
    $command = 'echo "'.str_replace('"','\"',$config_json_string).'" > /config/bmminer.conf';
    self::sshExec($ip, $pw, $command);

  }

  // used to update config for Antminers that use Bmminer (S9, etc)
  private function updateNetworkBmminer($network_json) {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $network_conf = "";
    $network_conf  = "hostname=".$network_json['hostname']."\n";
    $network_conf .= "ipaddress=".$network_json['ipaddress']."\n";
    $network_conf .= "netmask=".$network_json['netmask']."\n";
    $network_conf .= "gateway=".$network_json['gateway']."\n";
    $network_conf .= "dnsservers=\"".$network_json['dnsservers']."\"\n";

    $command = 'echo "'.str_replace('"','\"',$network_conf).'" > /config/network.conf';
    self::sshExec($ip, $pw, $command);

  }

  // Used to shutdown (change from ONLINE to IDLE) for Antminers that use Bmminer (S9, etc)
  private function shutdownBmminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $commands = array();
    $commands[] = "cp /config/bmminer.conf /config/bmminer.conf_shutdown";
    $commands[] = "mv /sbin/monitorcg /sbin/monitorcg_shutdown";
    $commands[] = "mv /usr/bin/monitor-recobtn /usr/bin/monitor-recobtn_shutdown";
    $commands[] = "mv /usr/bin/monitor-ipsig /usr/bin/monitor-ipsig_shutdown";
    $commands[] = "mv /usr/bin/bmminer /usr/bin/bmminer_shutdown";
    $commands[] = "/sbin/reboot";

    foreach ($commands as $k=>$command) {
      self::sshExec($ip, $pw, $command);
    }
  }

  // Used to startup (change from ONLINE to IDLE) for Antminers that use Bmminer (S9, etc)
  private function startupBmminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $commands = array();
    $commands[] = "rm /config/bmminer.conf_shutdown";
    $commands[] = "mv /sbin/monitorcg_shutdown /sbin/monitorcg";
    $commands[] = "mv /usr/bin/monitor-recobtn_shutdown /usr/bin/monitor-recobtn";
    $commands[] = "mv /usr/bin/monitor-ipsig_shutdown /usr/bin/monitor-ipsig";
    $commands[] = "mv /usr/bin/bmminer_shutdown /usr/bin/bmminer";
    $commands[] = "/sbin/reboot";

    foreach ($commands as $k=>$command) {
      self::sshExec($ip, $pw, $command);
    }
  }

  // Used to fetch state for Antminers that use Cgminer (L3, D3, A3, etc)
  private function stateCgminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    if (self::minerDirectoryExists('/config'))  {

      if (self::minerFileExists('/config/cgminer.conf_shutdown')) {
        return "IDLE";
      } else {
        return "ONLINE";
      }

    } else {
      return "OFFLINE";
    }

  }

  // Used to fetch state for Antminers that use Cgminer (L3, D3, A3, etc)
  private function configCgminer() {

     $ip = self::$ip;
     $pw = self::$pw;
     $type = self::$type;

     $command = "more /config/cgminer.conf";
     $miner_config = json_decode(self::sshExec($ip, $pw, $command), TRUE);
     self::$config = $miner_config;

     $command = "more /config/network.conf";
     $network_string = self::sshExec($ip, $pw, $command);

     $network_string_lines = explode(PHP_EOL, $network_string);

     $network_config = [];
     foreach ($network_string_lines as $k=>$v) {
       $keyval = explode("=",$v);

       switch ($keyval[0]) {
          case 'hostname':
              $network_config['hostname'] = $keyval[1];
              break;

          case 'ipaddress':
              $network_config['ipaddress'] = $keyval[1];
              break;

          case 'netmask':
              $network_config['netmask'] = $keyval[1];
              break;

          case 'gateway':
              $network_config['gateway'] = $keyval[1];
              break;

          case 'dnsservers':
              $network_config['dnsservers'] = str_replace('"','',$keyval[1]);
              break;
        }

     }
     self::$network = $network_config;

   }

   // used to update config for Antminers that use Cgminer (L3, D3, A3, etc)
   private function updateConfigCgminer($config_json) {

     $ip = self::$ip;
     $pw = self::$pw;
     $type = self::$type;

     $config_json_string = json_encode($config_json);

     $command = 'echo "'.str_replace('"','\"',$config_json_string).'" > /config/cgminer.conf';
     self::sshExec($ip, $pw, $command);

   }

   // used to update config for Antminers that use Cgminer (L3, D3, A3, etc)
   private function updateNetworkCgminer($network_json) {

     $ip = self::$ip;
     $pw = self::$pw;
     $type = self::$type;

     $network_conf = "";
     $network_conf  = "hostname=".$network_json['hostname']."\n";
     $network_conf .= "ipaddress=".$network_json['ipaddress']."\n";
     $network_conf .= "netmask=".$network_json['netmask']."\n";
     $network_conf .= "gateway=".$network_json['gateway']."\n";
     $network_conf .= "dnsservers=\"".$network_json['dnsservers']."\"\n";

     $command = 'echo "'.str_replace('"','\"',$network_conf).'" > /config/network.conf';
     self::sshExec($ip, $pw, $command);

   }

  // Used to shutdown (change from ONLINE to IDLE) for Antminers that use Cgminer (L3, D3, A3, etc)
  private function shutdownCgminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "more /config/cgminer.conf";
    $miner_config = json_decode(self::sshExec($ip, $pw, $command), TRUE);

    $miner_config_no_pools = $miner_config;
    $miner_config_no_pools['pools'] = [];

    $commands = [];
    $commands[] = "mv /config/cgminer.conf /config/cgminer.conf_shutdown";
    $commands[] = 'echo "'.str_replace('"','\"',json_encode($miner_config_no_pools)).'" > /config/cgminer.conf';
    $commands[] = "/sbin/reboot";

    foreach ($commands as $k=>$command) {
      self::sshExec($ip, $pw, $command);
    }
  }

  // Used to startup (change from IDLE to ONLINE) for Antminers that use Cgminer (L3, D3, A3, etc)
  private function startupCgminer() {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $commands = [];
    $commands[] = "rm /config/cgminer.conf";
    $commands[] = "mv /config/cgminer.conf_shutdown /config/cgminer.conf";
    $commands[] = "/sbin/reboot";

    foreach ($commands as $k=>$command) {
      self::sshExec($ip, $pw, $command);
    }
  }

  // Used to fetch info from Antminer
  private function fetchAntminerInfo() {
    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "more /proc/uptime";
    $uptime_string = self::sshExec($ip, $pw, $command);
    $uptime_segments = explode(" ", $uptime_string);

    self::$uptime = (float)$uptime_segments[0];

    $command = "more /proc/loadavg";
    $load_string = self::sshExec($ip, $pw, $command);
    $load_segments = explode(" ", $load_string);

    $load = array();
    $load['avg_1'] = (float)$load_segments[0];
    $load['avg_5'] = (float)$load_segments[1];
    $load['avg_15'] = (float)$load_segments[2];

    self::$load = $load;

    $port = '4028';

    define('SOCK_TIMEOUT', '3');
    ini_set('default_socket_timeout', SOCK_TIMEOUT);
    set_time_limit(0);

    if (!function_exists('analyzeHashboardChips'))   {
      function analyzeHashboardChips($chip_count_max, $chip_string) {

      $asc = str_replace(' ', '', $chip_string);
      $asc_chip_chars = str_split($asc);

      $asc_chip_ok = 0;
      $asc_chip_error = 0;
      $asc_chip_missing = 0;

      foreach ($asc_chip_chars as $asck=>$ascv) {
        if (strtolower($ascv) == 'o') {
          $asc_chip_ok++;
        }

        if (strtolower($ascv) == 'x') {
          $asc_chip_error++;
        }
      }

      $asc_chip_missing = $chip_count_max - ($asc_chip_ok + $asc_chip_error);

      return array(
        'ok' => $asc_chip_ok,
        'error' => $asc_chip_error,
        'missing' => $asc_chip_missing
      );
    }
    }

    if (!function_exists('seconds_to_time'))   {
      function seconds_to_time($input_seconds) {
      $seconds_in_minute = 60;
      $seconds_in_hour   = 60 * $seconds_in_minute;
      $seconds_in_day    = 24 * $seconds_in_hour;

      $days = floor($input_seconds / $seconds_in_day);

      $hour_seconds = $input_seconds % $seconds_in_day;
      $hours = floor($hour_seconds / $seconds_in_hour);

      $minute_seconds = $hour_seconds % $seconds_in_hour;
      $minutes = floor($minute_seconds / $seconds_in_minute);

      $seconds = ceil($remaining_seconds);
      $obj = array(
        'd' => (int)$days,
        'h' => sprintf('%02d', (int)$hours),
        'm' => sprintf('%02d', (int)$minutes),
        's' => sprintf('%02d', (int)$seconds)
      );
      return $obj;
    }
    }

    if (!function_exists('getsock'))   {
      function getsock($addr, $port) {
    	$socket = null;
    	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    	if ($socket === false || $socket === null) {
    		$error = socket_strerror(socket_last_error());
    		$msg = "socket create(TCP) failed";
    		return null;
    	}

    	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => SOCK_TIMEOUT, 'usec' => 0));
    	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => SOCK_TIMEOUT, 'usec' => 0));

    	$res = socket_connect($socket, $addr, $port);

    	if ($res === false) {
    		$error = socket_strerror(socket_last_error());
    		$msg = "socket connect($addr,$port) failed";
    		socket_close($socket);
    		return null;
    	}

    	return $socket;

    }
    }

    if (!function_exists('readsockline'))   {
      function readsockline($socket) {
    	$line = '';
    	while (true) {
    		$byte = socket_read($socket, 1);
    		if ($byte === false || $byte === '')
    			break;
    		if ($byte === "\0")
    			break;
    		$line .= $byte;
    	}
    	return $line;
    }
    }

    if (!function_exists('request'))   {
      function request($cmd, $ip, $port) {

    	$socket = getsock($ip, $port);

    	if ($socket != null) {
    		socket_write($socket, $cmd, strlen($cmd));

    		$line = readsockline($socket);
    		socket_close($socket);

    		if (strlen($line) == 0) {
    			return $line;
    		}

    		if (substr($line,0,1) == '{') {
          return json_decode($line, true);
        }

        $data = array();
    		$objs = explode('|', $line);

    		foreach ($objs as $obj) {

    			if (strlen($obj) > 0) {
    				$items = explode(',', $obj);
    				$item = $items[0];
    				$id = explode('=', $items[0], 2);

    				if (count($id) == 1 or !ctype_digit($id[1])) {
              $name = $id[0];
            } else {
              $name = $id[0].$id[1];
            }

    				if (strlen($name) == 0) {
              $name = 'null';
            }

    				if (isset($data[$name])) {

    					$num = 1;
    					while (isset($data[$name.$num])) {
                $num++;
                $name .= $num;
              }

    				}

    				$counter = 0;
    				foreach ($items as $item) {
    					$id = explode('=', $item, 2);

    					if (count($id) == 2) {
                $data[$name][$id[0]] = $id[1];
              } else {
                $data[$name][$counter] = $id[0];
              }

    					$counter++;

    				}

    			}

    		}
    		return $data;
    	} else {
        return null;
      }

    }
    }

    // SUMMARY
    $summary = request('summary', self::$ip, $port);
    unset($summary['SUMMARY']['0']);

    foreach ($summary['SUMMARY'] as $k=>$v) {
      $key = strtolower($k);
      $key = str_replace(' ','_',$key);
      $key = str_replace('%','_percent',$key);
      self::$summary[$key] = (float)$v;
    }


    // POOLS
    $pools = request('pools', self::$ip, $port);

    foreach ($pools['POOL0'] as $k=>$v) {
      $key = strtolower($k);
      $key = str_replace(' ','_',$key);
      $key = str_replace('%','_percent',$key);
      if (is_numeric($v)) {
        self::$pools['pool_1'][$key] = (float)$v;
      } else {
        self::$pools['pool_1'][$key] = $v;
      }

    }

    self::$pools['pool_1']['last_share_time'] = (float)self::$pools['pool_1']['last_share_time'];

    foreach ($pools['POOL1'] as $k=>$v) {
      $key = strtolower($k);
      $key = str_replace(' ','_',$key);
      $key = str_replace('%','_percent',$key);
      if (is_numeric($v)) {
        self::$pools['pool_2'][$key] = (float)$v;
      } else {
        self::$pools['pool_2'][$key] = $v;
      }

    }

    self::$pools['pool_2']['last_share_time'] = (float)self::$pools['pool_2']['last_share_time'];

    foreach ($pools['POOL2'] as $k=>$v) {
      $key = strtolower($k);
      $key = str_replace(' ','_',$key);
      $key = str_replace('%','_percent',$key);
      if (is_numeric($v)) {
        self::$pools['pool_3'][$key] = (float)$v;
      } else {
        self::$pools['pool_3'][$key] = $v;
      }

    }

    self::$pools['pool_3']['last_share_time'] = (float)self::$pools['pool_3']['last_share_time'];

    // STATS
    $stats = request('stats', self::$ip, $port);

    if (isset($stats['BMMiner'])) {
      $miner_type_details = $stats['BMMiner'];
    }

    if (isset($stats['CGMiner'])) {
      $miner_type_details = $stats['CGMiner'];
    }

    self::$summary['miner_hardware_version'] = $miner_type_details['Miner'];
    self::$summary['miner_type'] = $miner_type_details['Type'];
    self::$summary['miner_firmware'] = $miner_type_details['CompileTime'];

    self::$summary['miner_frequency'] = $stats['STATS0']['frequency'];
    self::$summary['hashboard_count'] = $stats['STATS0']['miner_count'];

    switch (self::$summary['miner_type']) {
      case 'Antminer S9' :
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn6'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn7'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn8'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_6'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_7'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_8'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'])/3;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate6'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate7'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate8'];

        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'];


        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs6']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs7']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs8']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'];

      break;

      case 'Antminer S9i' :
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn6'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn7'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn8'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_6'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_7'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_8'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'])/3;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate6'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate7'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate8'];

        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'];

        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs6']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs7']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(63,$stats['STATS0']['chain_acs8']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'];

      break;

      case 'Antminer L3+':
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        $asic_chip_total = 288;
        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn1'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn2'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn3'];
        self::$stats['asic_hashboard_4_count'] = (float)$stats['STATS0']['chain_acn4'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'] +
                                                                self::$stats['asic_hashboard_4_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_1'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_2'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_3'];
        self::$stats['asic_hashboard_4_temp'] = (float)$stats['STATS0']['temp2_4'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'] +
                                                              self::$stats['asic_hashboard_4_temp'])/4;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate1'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate2'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate3'];
        self::$stats['asic_hashboard_4_rate'] = (float)$stats['STATS0']['chain_rate4'];
        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'] +
                                                               self::$stats['asic_hashboard_4_rate'];

        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs1']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs2']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs3']);
        self::$stats['asic_hashboard_4_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs4']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'] +
          self::$stats['asic_hashboard_4_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'] +
          self::$stats['asic_hashboard_4_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'] +
          self::$stats['asic_hashboard_4_chip_status']['missing'];

      break;

      case 'Antminer L3++':
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        $asic_chip_total = 288;
        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn1'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn2'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn3'];
        self::$stats['asic_hashboard_4_count'] = (float)$stats['STATS0']['chain_acn4'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'] +
                                                                self::$stats['asic_hashboard_4_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_1'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_2'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_3'];
        self::$stats['asic_hashboard_4_temp'] = (float)$stats['STATS0']['temp2_4'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'] +
                                                              self::$stats['asic_hashboard_4_temp'])/4;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate1'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate2'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate3'];
        self::$stats['asic_hashboard_4_rate'] = (float)$stats['STATS0']['chain_rate4'];
        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'] +
                                                               self::$stats['asic_hashboard_4_rate'];

        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs1']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs2']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs3']);
        self::$stats['asic_hashboard_4_chip_status'] = analyzeHashboardChips(72,$stats['STATS0']['chain_acs4']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'] +
          self::$stats['asic_hashboard_4_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'] +
          self::$stats['asic_hashboard_4_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'] +
          self::$stats['asic_hashboard_4_chip_status']['missing'];

      break;

      case 'Antminer A3':
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        $asic_chip_total = 180;
        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn1'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn2'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn3'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_1'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_2'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_3'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'])/3;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate1'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate2'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate3'];
        self::$stats['asic_hashboard_4_rate'] = (float)$stats['STATS0']['chain_rate4'];
        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'];

        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs1']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs2']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs3']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'];

      break;

      case 'Antminer D3':
        self::$stats = [];

        if (isset($stats['STATS0']['fan1'])) {
          self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        } else {
          self::$stats['fan_rpm_1'] = 0;
        }

        if (isset($stats['STATS0']['fan2'])) {
          self::$stats['fan_rpm_2'] = (float)$stats['STATS0']['fan2'];
        } else {
          self::$stats['fan_rpm_2'] = 0;
        }

        if (isset($stats['STATS0']['fan3'])) {
          self::$stats['fan_rpm_3'] = (float)$stats['STATS0']['fan3'];
        } else {
          self::$stats['fan_rpm_3'] = 0;
        }

        if (isset($stats['STATS0']['fan4'])) {
          self::$stats['fan_rpm_4'] = (float)$stats['STATS0']['fan4'];
        } else {
          self::$stats['fan_rpm_4'] = 0;
        }

        if (isset($stats['STATS0']['fan5'])) {
          self::$stats['fan_rpm_5'] = (float)$stats['STATS0']['fan5'];
        } else {
          self::$stats['fan_rpm_5'] = 0;
        }

        if (isset($stats['STATS0']['fan6'])) {
          self::$stats['fan_rpm_6'] = (float)$stats['STATS0']['fan6'];
        } else {
          self::$stats['fan_rpm_6'] = 0;
        }

        if (isset($stats['STATS0']['fan7'])) {
          self::$stats['fan_rpm_7'] = (float)$stats['STATS0']['fan7'];
        } else {
          self::$stats['fan_rpm_7'] = 0;
        }

        if (isset($stats['STATS0']['fan8'])) {
          self::$stats['fan_rpm_8'] = (float)$stats['STATS0']['fan8'];
        } else {
          self::$stats['fan_rpm_8'] = 0;
        }

        $asic_chip_total = 180;
        self::$stats['asic_hashboard_1_count'] = (float)$stats['STATS0']['chain_acn1'];
        self::$stats['asic_hashboard_2_count'] = (float)$stats['STATS0']['chain_acn2'];
        self::$stats['asic_hashboard_3_count'] = (float)$stats['STATS0']['chain_acn3'];

        self::$stats['asic_hashboard_total_count'] = self::$stats['asic_hashboard_1_count'] +
                                                                self::$stats['asic_hashboard_2_count'] +
                                                                self::$stats['asic_hashboard_3_count'];

        self::$stats['asic_hashboard_1_temp'] = (float)$stats['STATS0']['temp2_1'];
        self::$stats['asic_hashboard_2_temp'] = (float)$stats['STATS0']['temp2_2'];
        self::$stats['asic_hashboard_3_temp'] = (float)$stats['STATS0']['temp2_3'];

        self::$stats['asic_hashboard_avg_temp'] = (self::$stats['asic_hashboard_1_temp'] +
                                                              self::$stats['asic_hashboard_2_temp'] +
                                                              self::$stats['asic_hashboard_3_temp'])/3;

        self::$stats['asic_hashboard_1_rate'] = (float)$stats['STATS0']['chain_rate1'];
        self::$stats['asic_hashboard_2_rate'] = (float)$stats['STATS0']['chain_rate2'];
        self::$stats['asic_hashboard_3_rate'] = (float)$stats['STATS0']['chain_rate3'];
        self::$stats['asic_hashboard_4_rate'] = (float)$stats['STATS0']['chain_rate4'];
        self::$stats['asic_hashboard_rate_total'] = self::$stats['asic_hashboard_1_rate'] +
                                                               self::$stats['asic_hashboard_2_rate'] +
                                                               self::$stats['asic_hashboard_3_rate'];

        self::$stats['asic_hashboard_1_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs1']);
        self::$stats['asic_hashboard_2_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs2']);
        self::$stats['asic_hashboard_3_chip_status'] = analyzeHashboardChips(60,$stats['STATS0']['chain_acs3']);

        self::$stats['asic_hashboard_total_chip_status_ok'] =
          self::$stats['asic_hashboard_1_chip_status']['ok'] +
          self::$stats['asic_hashboard_2_chip_status']['ok'] +
          self::$stats['asic_hashboard_3_chip_status']['ok'];

        self::$stats['asic_hashboard_total_chip_status_error'] =
          self::$stats['asic_hashboard_1_chip_status']['error'] +
          self::$stats['asic_hashboard_2_chip_status']['error'] +
          self::$stats['asic_hashboard_3_chip_status']['error'];

        self::$stats['asic_hashboard_total_chip_status_missing'] =
          self::$stats['asic_hashboard_1_chip_status']['missing'] +
          self::$stats['asic_hashboard_2_chip_status']['missing'] +
          self::$stats['asic_hashboard_3_chip_status']['missing'];

       break;

       default:

       break;
    }

    return;
  }

  // Helper function used to interrogate miner via SSH (used my various functions in this class)
  private function sshExec($ip, $pw, $command) {
    $shell_exec = "sshpass -p '".$pw."' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=120 root@".$ip." '".$command."'";
    return shell_exec($shell_exec);
  }

  // Helper function used to see if file exists on miner
  private function minerFileExists($filepath) {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "[ -f $filepath ] && echo '1' || echo '0'";
    $response = self::sshExec($ip, $pw, $command);

    if ($response == 1) {
      return TRUE;
    } else {
      return FALSE;
    }

  }

  // Helper function used to see if directory exists on miner
  private function minerDirectoryExists($dirpath) {

    $ip = self::$ip;
    $pw = self::$pw;
    $type = self::$type;

    $command = "[ -d $dirpath ] && echo '1' || echo '0'";
    $response = self::sshExec($ip, $pw, $command);

    if ($response == 1) {
      return TRUE;
    } else {
      return FALSE;
    }

  }

  function __construct($ip, $pw, $type) {

    self::$ip   = $ip;
    self::$pw   = $pw;
    self::$type = $type;

    self::fetchAntminerState();

    if (self::$state == "ONLINE") {
      self::fetchAntminerConfig();
      self::fetchAntminerInfo();
    }


  }

}

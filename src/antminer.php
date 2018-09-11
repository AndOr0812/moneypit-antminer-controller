<?php

class Antminer {

  public static $ip;
  public static $pw;
  public static $type;

  public static $state;
  public static $summary;
  public static $pools;
  public static $stats;

  function getIp() {
    return self::$ip;
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

  function fetchAntminerState() {

    function stateBmminer($ip, $pw) {

      $command = 'test -f /config; echo $?';
      $shell_exec = "sshpass -p '".$pw."' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@".$ip." '".$command."'";

      $connect_check = shell_exec($shell_exec);

      if ($connect_check == 1)  {

        $command = 'test -f /config/bmminer.conf_shutdown; echo $?';
        $miner_conf_check = shell_exec("sshpass -p '".$pw."' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@".$ip." '".$command."'");

        if ($miner_conf_check == 1) {
          return "ONLINE";
        } else {
          return "IDLE";
        }

      } else {

        return "OFFLINE";

      }

    }

    function stateCgminer($ip, $pw) {

      $command = 'test -f /config; echo $?';
      $shell_exec = "sshpass -p '".$pw."' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@".$ip." '".$command."'";

      $connect_check = shell_exec($shell_exec);

      if ($connect_check == 1)  {

        $command = 'test -f /config/cgmminer.conf_shutdown; echo $?';
        $miner_conf_check = shell_exec("sshpass -p '".$pw."' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@".$ip." '".$command."'");

        if ($miner_conf_check == 1) {
          return "ONLINE";
        } else {
          return "IDLE";
        }

      } else {

        return "OFFLINE";

      }

    }

    switch (self::$type) {
      case 'S9' :
        self::$state = stateBmminer(self::$ip, self::$pw);
      break;

      case 'S9i' :
        self::$state = stateBmminer(self::$ip, self::$pw);
      break;

      case 'D3' :
        self::$state = stateCgminer(self::$ip, self::$pw);
      break;

      case 'A3' :
        self::$state = stateCgminer(self::$ip, self::$pw);
      break;

      case 'L3' :
        self::$state = stateCgminer(self::$ip, self::$pw);
      break;

      default:
        self::$state = null;
      break;
    }

  }

  function fetchAntminerInfo() {



    $port = '4028';

    define('SOCK_TIMEOUT', '3');
    ini_set('default_socket_timeout', SOCK_TIMEOUT);
    set_time_limit(0);

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


    // SUMMARY
    $summary = request('summary', self::$ip, $port);

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

        self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan3'];
        self::$stats['ran_rpm_2'] = (float)$stats['STATS0']['fan6'];

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

        self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan3'];
        self::$stats['ran_rpm_2'] = (float)$stats['STATS0']['fan6'];

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

        self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        self::$stats['ran_rpm_2'] = (float)$stats['STATS0']['fan2'];

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

        self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        self::$stats['ran_rpm_2'] = (float)$stats['STATS0']['fan2'];

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

        self::$stats['fan_rpm_1'] = (float)$stats['STATS0']['fan1'];
        self::$stats['ran_rpm_2'] = (float)$stats['STATS0']['fan2'];

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


     //self::$stats

    return;
  }

  function __construct($ip, $pw, $type) {

    self::$ip   = $ip;
    self::$pw   = $pw;
    self::$type = $type;

    self::fetchAntminerState();

    if (self::$state == "ONLINE") {
      self::fetchAntminerInfo();
    }



  }

}

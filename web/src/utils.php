<?php
/*
* NetApp OnCommand API Services
*
* Listing Clusters, Nodes, Aggregates, Volumes and Lifs
*
* PHP version 7
*
* @author Adly Taibi
* @version 1.0
*/

function uwrap($hostname,$port) {
  return "https://$hostname:$port/api/1.0/ontap/";
}

# Read configuration from file
function readendpoint() {
  if (file_exists("endpoint.php")) {
    $api = fopen("endpoint.php", "r") or print "<div class='alert alert-warning' role='alert'>Error: reading endpoint file.</div>";
    $eparray = parse_ini_file("endpoint.php");
    $_SESSION['endpoint'] = $eparray['endpoint'];
    $_SESSION['port'] = $eparray['port'];
    $_SESSION['username'] = $eparray['username'];
    $_SESSION['password'] = $eparray['password'];
    $_SESSION['uri'] = uwrap($eparray['endpoint'],$eparray['port']);
    fclose($api);
  } else {
    session_unset();
    return "<div class='alert alert-warning' role='alert'>Please, set endpoint.</div>";
  }
  # Create cache directory if it doesn't already exist
  if (!file_exists('cache')) {
    mkdir('cache', 0755, true);
  }
  # Initialize some session keys
  $_SESSION['breadcrumbs'] = array();
  $_SESSION['offline'] = '';
}

# User interaction
function postdata() {
  if (isset($_POST['btn'])) {
    if ($_POST['btn']=='save') {
      $_SESSION['endpoint'] = $_POST['endpoint'];
      $_SESSION['port'] = $_POST['port'];
      $_SESSION['username'] = $_POST['username'];
      $_SESSION['password'] = $_POST['password'];
      $_SESSION['uri'] = uwrap($_POST['endpoint'],$_SESSION['port']);
      # Remember and save the endpoint to file
      $api = fopen("endpoint.php", "w") or print "<div class='alert alert-warning' role='alert'>Error: writing endpoint file.</div>";
      fwrite($api, implode(PHP_EOL,['<?php/*','endpoint="'.$_POST['endpoint'].'"','port="'.$_POST['port'].'"','username="'.$_POST['username'].'"','password="'.$_POST['password'].'"','*/?>']));
      fclose($api);
      header('location: .');
    }
  }
}

function seconds2time($seconds) {
	$dtF = new DateTime("@0");
	$dtT = new DateTime("@$seconds");
	$days = $dtF->diff($dtT)->format('%a');
	$hours = $dtF->diff($dtT)->format('%h');
	$minutes = $dtF->diff($dtT)->format('%i');
	$seconds = $dtF->diff($dtT)->format('%s');
	$stime = '';
	$stime .= ($days==1)?"$days day ":'';
	$stime .= ($days>1)?"$days days ":'';
	$stime .= ($hours==1)?"$hours hour ":'';
	$stime .= ($hours>1)?"$hours hours ":'';
	$stime .= ($minutes==1)?"$minutes minute ":'';
	$stime .= ($minutes>1)?"$minutes minutes ":'';
	$stime .= ($seconds==1)?"$seconds second":'';
	$stime .= ($seconds>1)?"$seconds seconds":'';
	return $stime;
}
function fbytes($size, $precision = 2)
{
	if (!is_numeric($size)) { return; }
	if ($size==0) { return 0; }
	$base = log($size, 1024);
	$suffixes = array('', 'kB', 'MB', 'GB', 'TB');   
	return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}
function curlit($url,$next) {
	global $connup;
  $result = '';
	$cachetime = 600;
	if ($next && $next!='.') {
		$pos = strpos($url,'?',1);
		$ncall = substr($url,0,$pos);
		$ntag = '?nextTag='.urlencode($next);
		$url = $ncall.$ntag;
	}
	$fcache = 'cache/'.md5(urlencode($url));
	$fetchit = false;
	$msg = '';
	if (file_exists($fcache)) {
		$tcache = filemtime($fcache);
		$elapsed = (time()-$tcache);
		if ($elapsed < $cachetime) {
			$result = file_get_contents($fcache);
		} else {
			$fetchit = true;
		}
	} else {
		$fetchit = true;
	}
	if ($fetchit) {
		if ($connup) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			$header = array();
      $creds = base64_encode(implode('',[$_SESSION['username'],':',$_SESSION['password']]));
			$header[] = "Authorization: Basic $creds";
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,3); 
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($curl);
			$errno = curl_errno($curl);
			curl_close($curl);
		} else {
			$errno = 7;
		}
		if($errno>0) {
			if (file_exists($fcache)) {
				$result = file_get_contents($fcache);
				$tcache = filemtime($fcache);
				$elapsed = seconds2time(time()-$tcache);
        $msg = "<div class='alert alert-warning' role='alert'>Server unreachable. Displaying data from $elapsed. <form action='.' style='display:inline' method=post>Work offline <input type=checkbox name=offline onChange='submit();' ".$_SESSION['offline']."></form></div>";
			} else {
				$error_message = curl_strerror($errno);
        $msg = "<div class='alert alert-warning' role='alert'>Server unreachable. ($error_message)</div>";
			}
		} else {
			file_put_contents($fcache,$result);
		}
	}
	$json = json_decode($result,true);
	if (isset($json['result']['nextTag'])) {
		$next = $json['result']['nextTag'];
	} else {
		$next = '';
	}
	return array($json,$msg,$next);
}
function metable($json,$thead,$columns,$urls,$ucall) {
  $api = $_SESSION['uri'];
	$table = '';
	for ($j=0; $j < $json['result']['total_records']; $j++) {
		$table .= '<tr>';
		for ($i=0; $i < count($thead); $i++) {
			$key = $json['result']['records'][$j]['key'];
			$value = (isset($json['result']['records'][$j][$columns[$i]]))?$json['result']['records'][$j][$columns[$i]]:'';
			if ($urls[$i]) {
				if ($urls[$i]=='state') {
					$state = ($value)?'green':'red';
					$table .= "<td data-search='".$value."' align=center><img src=images/".$state.'.png></td>';
				} else {
					switch ($columns[$i]) {
						case ('tr_aggr'):
							$tcall = str_replace('%key%',$key,$ucall[$i]);
							$tot = 0;
							$next = '.';
							while ($next) {
								list($json2,$msg,$next) = curlit($api.$tcall,$next);
								$tot += $json2['result']['total_records'];
							}
							$value = str_replace('%key%',$key,$urls[$i]);
							$value = str_replace('%aggr%',$json['result']['records'][$j]['name'],$value);
							$table .= "<td data-search='".$tot."'><a href='".$value."'>".$tot.'</a></td>';
							break;
						case ('tr_clusters'):
							$name = $json['result']['records'][$j]['name'];
							$tcall = str_replace('%key%',$key,$urls[$i]);
							$tcall = str_replace('%cluster%',$name,$tcall);
							$value = $ucall[$i][$key];
							$table .= "<td data-search='".$value."'><a href='".$tcall."'>".$value.'</a></td>';
							break;
						case ('tr_svm'):
							$tcall = str_replace('%key%',$key,$ucall[$i]);
							$tot = 0;
							$next = '.';
							while ($next) {
								list($json2,$msg,$next) = curlit($api.$tcall,$next);
								$tot += $json2['result']['total_records'];
							}
							$value = str_replace('%key%',$key,$urls[$i]);
							$value = str_replace('%svm%',$json['result']['records'][$j]['name'],$value);
							$table .= "<td data-search='".$tot."'><a href='".$value."'>".$tot.'</a></td>';
							break;
						default;
							$table .= "<td data-search='".$value."'><a href='".$urls[$i]."'>".$value.'</a></td>';
					}
				}
			} else {
				if ($columns[$i]=='version') { $value = substr($value,15,7); }
				if ($columns[$i]=='tr_clusters_size') { $value = fbytes($ucall[$i][$key]); }
				if ($columns[$i]=='tr_clusters_size_pct') {
					$value = round((1-$ucall[$i-1][$key]/$ucall[$i-2][$key])*100,0).'%';
				}
				if ($columns[$i]=='size_total') { $value = fbytes($value); }
				if ($columns[$i]=='size_avail') { $value = fbytes($value); }
				if ($columns[$i]=='size_used_percent') { $value = $value.'%'; }
				if (substr($columns[$i],-7,7)=='enabled') { $value = ($value)?'Enabled':''; }
				if ($columns[$i]=='tr_vola') { $key = $json['result']['records'][$j]['storage_vm_key']; $value = $ucall[$i][$key]; }
				if ($columns[$i]=='tr_vols') { $key = $json['result']['records'][$j]['aggregate_key']; $value = $ucall[$i][$key]; }
				if ($columns[$i]=='tr_lif') { $key = $json['result']['records'][$j]['storage_vm_key']; $value = $ucall[$i][$key]; }
				$table .= "<td data-search='".$value."'>".$value.'</td>';
			}
		}
		$table .= '</tr>';
	}
	return $table;
}
function mehead($thead) {
  $mehead = breadcrumbs();
	$mehead .= "<table border=1 id='smtable' class='stripe' cellspacing='0' width='100%'>";
	$tmsg = '<tr>';
	for ($i=0; $i < count($thead); $i++) {
		$tmsg .= '<th>'.$thead[$i].'</th>';
	}
	$tmsg .= '</tr>';
	$mehead .= '<thead>'.$tmsg.'</thead>';
	return $mehead;
}
function mefoot($thead) {
	$tmsg = '<tr>';
	for ($i=0; $i < count($thead); $i++) {
		$tmsg .= '<th>'.$thead[$i].'</th>';
	}
	$tmsg .= '</tr>';
	$mefoot = '<tfoot>'.$tmsg.'</tfoot><tbody>';
	$mefoot .= '</tbody></table>';
	return $mefoot;
}
function breadcrumbs() {
  $bc = '';
  if (sizeof($_SESSION['breadcrumbs'])>0) {
    $bc = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    foreach ($_SESSION['breadcrumbs'] as $key => $breadcrumb) {
      parse_str(parse_url($breadcrumb)['query'], $qlist);
      $svm = (array_key_exists('svm', $qlist))?'::'.$qlist['svm']:'';
      $qdetail = $qlist['cluster'].$svm.'::'.key($qlist);
      $here = ($_SERVER['REQUEST_URI'] == $breadcrumb)?true:false;
      if ($here) {
        $bc .= "<li class='breadcrumb-item active' aria-current='page'>$qdetail</li>";
        if ($key>0) {
          $_SESSION['breadcrumbs'] = array_slice($_SESSION['breadcrumbs'], 0, $key);
        }
        break;
      } else {
        $bc .= "<li class='breadcrumb-item'><a href='$breadcrumb'>$qdetail</a></li>";
      }
    }
    $bc .= '</ol></nav>';
  }
  return $bc;
}

# Endpoint checks and grabs
$title = 'Clusters';
$melist = '';
if (isset($_SESSION['endpoint']) && isset($_SESSION['port'])) {
  $msg = '';
  if (strlen($_SESSION['endpoint'])<=2 || strlen($_SESSION['port'])<=2) {
    readendpoint();
  }
  $_SESSION['uri'] = uwrap($_SESSION['endpoint'],$_SESSION['port']);
  $api = $_SESSION['uri'];
  $host = parse_url($api,PHP_URL_HOST);
  $port = parse_url($api,PHP_URL_PORT);
  if (isset($_POST['offline'])) {
    $_SESSION['offline'] = ($_POST['offline']=='on')?'checked':'';
  }
  if ($_SESSION['offline']=='checked') {
    $connup = false;
  } else {
    $timeout = 1;
    if ($sock = @fsockopen($host, $port, $errNo, $errStr, $timeout)) {
    	$connup = true;
    	fclose($sock);
    } else {
    	$connup = false;
    }
  }
  if (!in_array($_SERVER['REQUEST_URI'], $_SESSION['breadcrumbs'])) {
    array_push($_SESSION['breadcrumbs'], $_SERVER['REQUEST_URI']);
  }

  # Nodes
  if (isset($_GET['nodes'])) {
  	$title = $_GET['cluster'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'nodes?cluster_key='.$_GET['nodes'],$next);
  	$thead = ['name','model','location','serial number','healthy','failover state','failover enabled'];
  	$columns = ['name','model','location','serial_number','is_node_healthy','failover_state','is_failover_enabled'];
  	$urls = ['','','','','state','','state'];
  	$melist .= mehead($thead);
  	$melist .= metable($json,$thead,$columns,$urls,null);
  	$melist .= mefoot($thead);
  }
  # Aggregates
  if (isset($_GET['aggrs'])) {
  	$title = $_GET['cluster'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'clusters/'.$_GET['aggrs'].'/aggregates?has_local_root=false',$next);
  	$thead = ['name','volumes','total size','available','used','state','hybrid'];
  	$columns = ['name','tr_aggr','size_total','size_avail','size_used_percent','state','is_hybrid'];
  	$urls = ['','?vola=%key%&cluster='.$_GET['cluster'].'&aggr=%aggr%','','','','state',''];
  	$ucall = ['','aggregates/%key%/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false','','','','',''];
  	$melist .= mehead($thead);
  	$melist .= metable($json,$thead,$columns,$urls,$ucall);
  	$melist .= mefoot($thead);
  }
  # Volumes SVM-wide
  if (isset($_GET['vola']) || isset($_GET['vols'])) {
  	$title = $_GET['cluster'];
  	if (isset($_GET['vola'])) {
  		$next = '.';
  		list($json,$msg,$next) = curlit($api.'aggregates/'.$_GET['vola'].'/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false',$next);
  		$title .= '::'.$_GET['aggr'];
  		$next = '.';
  		list($json1,$msg,$next) = curlit($api.'storage-vms?type=data',$next);
  		for ($i=0; $i < $json1['result']['total_records']; $i++) {
  			$svm[$json1['result']['records'][$i]['key']]=$json1['result']['records'][$i]['name'];
  		}
  	} else {
  		$title .= '::'.$_GET['svm'];
  		$next = '.';
  		list($json1,$msg,$next) = curlit($api.'aggregates?has_local_root=false',$next);
  		for ($i=0; $i < $json1['result']['total_records']; $i++) {
  			$aggr[$json1['result']['records'][$i]['key']]=$json1['result']['records'][$i]['name'];
  		}
  	}
  	if (isset($_GET['vola'])) {
  		$thead = ['name','total size','available','used','state','junction path','style','SVM'];
  		$columns = ['name','size_total','size_avail','size_used_percent','state','junction_path','style','tr_vola'];
  		$urls = ['','','','','','','',''];
  		$ucall = ['','','','','','','',$svm];
  		$melist .= mehead($thead);
  		$melist .= metable($json,$thead,$columns,$urls,$ucall);
  	} else {
  		$thead = ['name','total size','available','used','state','junction path','style','aggregate'];
  		$columns = ['name','size_total','size_avail','size_used_percent','state','junction_path','style','tr_vols'];
  		$urls = ['','','','','','','',''];
  		$ucall = ['','','','','','','',$aggr];
  		$melist .= mehead($thead);
  		$next = '.';
  		while ($next) {
  			list($json,$msg,$next) = curlit($api.'storage-vms/'.$_GET['vols'].'/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false',$next);
  			$melist .= metable($json,$thead,$columns,$urls,$ucall);
  		}
  	}
  	$melist .= mefoot($thead);
  }
  # Volumes Cluster-wide
  if (isset($_GET['volc'])) {
  	$title = $_GET['cluster'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'aggregates?has_local_root=false',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$aggr[$json['result']['records'][$i]['key']]=$json['result']['records'][$i]['name'];
  	}
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?cluster_key='.$_GET['volc'].'&type=data',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$svm[$json['result']['records'][$i]['key']]=$json['result']['records'][$i]['name'];
  	}
  	$thead = ['name','total size','available','used','state','junction path','style','SVM','aggr'];
  	$columns = ['name','size_total','size_avail','size_used_percent','state','junction_path','style','tr_vola','tr_vols'];
  	$urls = ['','','','','','','','',''];
  	$ucall = ['','','','','','','',$svm,$aggr];
  	$melist .= mehead($thead);
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?cluster_key='.$_GET['volc'].'&type=data',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$next = '.';
  		while ($next) {
  			list($json2,$msg,$next) = curlit($api.'storage-vms/'.$json['result']['records'][$i]['key'].'/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false',$next);
  			$melist .= metable($json2,$thead,$columns,$urls,$ucall);
  		}
  	}
  	$melist .= mefoot($thead);
  }
  # SVMs
  if (isset($_GET['svms'])) {
  	$title = $_GET['cluster'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?cluster_key='.$_GET['svms'].'&type=data',$next);
  	$thead = ['name','volumes','LIFs','NFS','CIFS','iSCSI','state','CIFS server'];
  	$columns = ['name','tr_svm','tr_svm','nfs_enabled','cifs_enabled','iscsi_enabled','operational_state','cifs_server'];
  	$urls = ['','?vols=%key%&cluster='.$_GET['cluster'].'&svm=%svm%','?lifs=%key%&cluster='.$_GET['cluster'].'&svm=%svm%','','','','',''];
  	$ucall = ['','storage-vms/%key%/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false','network-lifs?storage_vm_key=%key%&role=data','','','','',''];
  	$melist .= mehead($thead);
  	$melist .= metable($json,$thead,$columns,$urls,$ucall);
  	$melist .= mefoot($thead);
  }
  # LIFs SVM-wide
  if (isset($_GET['lifs'])) {
  	$title = $_GET['cluster'].'::'.$_GET['svm'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'network-lifs?storage_vm_key='.$_GET['lifs'].'&role=data',$next);
  	$thead = ['name','address','netmask','is home','protocols','admin status','oper status'];
  	$columns = ['name','address','netmask','is_home','data_protocols','administrative_status','operational_status'];
  	$urls = ['','','','state','','',''];
  	$ucall = ['','','','','','',''];
  	$melist .= mehead($thead);
  	$melist .= metable($json,$thead,$columns,$urls,$ucall);
  	$melist .= mefoot($thead);
  }
  # LIFs Cluster-wide
  if (isset($_GET['lifc'])) {
  	$title = $_GET['cluster'];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?cluster_key='.$_GET['lifc'].'&type=data',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$svm[$json['result']['records'][$i]['key']]=$json['result']['records'][$i]['name'];
  	}
  	$thead = ['name','address','netmask','is home','protocols','admin status','oper status','SVM'];
  	$columns = ['name','address','netmask','is_home','data_protocols','administrative_status','operational_status','tr_lif'];
  	$urls = ['','','','state','','','',''];
  	$ucall = ['','','','','','','',$svm];
  	$melist .= mehead($thead);
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?cluster_key='.$_GET['lifc'].'&type=data',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		list($json2,$msg,$next) = curlit($api.'network-lifs?storage_vm_key='.$json['result']['records'][$i]['key'].'&role=data',$next);
  		$melist .= metable($json2,$thead,$columns,$urls,$ucall);
  	}
  	$melist .= mefoot($thead);
  }
  # Clusters
  if (count($_GET)==0) {
    $_SESSION['breadcrumbs'] = array();
  	$title = 'Clusters';
  	# Get a list of nodes
  	$node=[];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'nodes',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$key = $json['result']['records'][$i]['cluster_key'];
  		if (isset($node[$key])) {
  			$node[$key]++;
  		} else {
  			$node[$key] = 1;
  		}
  		$nodecluster[$json['result']['records'][$i]['key']]=$key;
  	}
  	# Gel a list of aggregates
  	$aggr=[];
  	$aggrstot=[];
  	$aggravail=[];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'aggregates?has_local_root=false',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		$key = $json['result']['records'][$i]['node_key'];
  		if (isset($aggr[$nodecluster[$key]])) {
  			$aggr[$nodecluster[$key]]++;
  		} else {
  			$aggr[$nodecluster[$key]] = 1;
  		}
  		if (isset($aggrstot[$nodecluster[$key]])) {
  			$aggrstot[$nodecluster[$key]]+=$json['result']['records'][$i]['size_total'];
  		} else {
  			$aggrstot[$nodecluster[$key]]=0;
  		}
  		if (isset($aggravail[$nodecluster[$key]])) {
  			$aggravail[$nodecluster[$key]]+=$json['result']['records'][$i]['size_avail'];
  		} else {
  			$aggravail[$nodecluster[$key]]=0;
  		}
  	}
  	# Get a list of SVMs
  	$svm=[];
  	$vol=[];
  	$lif=[];
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'storage-vms?type=data',$next);
  	for ($i=0; $i < $json['result']['total_records']; $i++) {
  		if (isset($svm[$json['result']['records'][$i]['cluster_key']])) {
  			$svm[$json['result']['records'][$i]['cluster_key']]++;
  		} else {
  			$svm[$json['result']['records'][$i]['cluster_key']] = 1;
  		}
  		# Get a list of LIFs
  		$next = '.';
  		list($json2,$msg,$next) = curlit($api.'network-lifs?role=data&storage_vm_key='.$json['result']['records'][$i]['key'].'&role=data',$next);
  		if (isset($lif[$json['result']['records'][$i]['cluster_key']])) {
  			$lif[$json['result']['records'][$i]['cluster_key']] += $json2['result']['total_records'];
  		} else {
  			$lif[$json['result']['records'][$i]['cluster_key']] = $json2['result']['total_records'];
  		}
  		# Get a list of volumes
  		$next = '.';
  		while ($next) {
  			list($json2,$msg,$next) = curlit($api.'storage-vms/'.$json['result']['records'][$i]['key'].'/volumes?is_storage_vm_root=false&is_load_sharing_mirror=false',$next);
  			if (isset($vol[$json['result']['records'][$i]['cluster_key']])) {
  				$vol[$json['result']['records'][$i]['cluster_key']] += $json2['result']['total_records'];
  			} else {
  				$vol[$json['result']['records'][$i]['cluster_key']] = $json2['result']['total_records'];
  			}
  		}
  	}
  	$next = '.';
  	list($json,$msg,$next) = curlit($api.'clusters',$next);
  	$thead = ['name','nodes','aggrs','SVMs','vols','LIFs','total size','available','used','version','status','contact'];
  	$columns = ['name','tr_clusters','tr_clusters','tr_clusters','tr_clusters','tr_clusters','tr_clusters_size','tr_clusters_size','tr_clusters_size_pct','version','status','contact'];
  	$url = '%key%&cluster=%cluster%';
  	$urls = ['',"?nodes=$url","?aggrs=$url","?svms=$url","?volc=$url","?lifc=$url",'','','','','',''];
  	$ucall = ['',$node,$aggr,$svm,$vol,$lif,$aggrstot,$aggravail,'','','',''];
  	$melist .= mehead($thead);
  	$melist .= metable($json,$thead,$columns,$urls,$ucall);
  	$melist .= mefoot($thead);
  }
} else {
  $msg = readendpoint();
}

function endpointform() {
  $endpoint = (isset($_SESSION['endpoint']))?$_SESSION['endpoint']:'';
  $port = (isset($_SESSION['port']))?$_SESSION['port']:'';
  $username = (isset($_SESSION['username']))?$_SESSION['username']:'';
  $password = (isset($_SESSION['password']))?$_SESSION['password']:'';
  print '<!-- endpoint form -->
<button type="button" name="btn" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#setting"><i class="fa fa-cog" style="font-size:18px;"></i></button>
<span class="group-btn badge badge-pill badge-info">API Endpoint: '.$_SESSION['uri'].'</span>
<form action=. method=post>
  <div class="modal fade" id="setting" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="Setting">Endpoint</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-close"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input name="endpoint" class="form-control input-sm chat-input" value="'.$endpoint.'" placeholder="endpoint hostname or IP address" pattern="^((([a-zA-Z]|[a-zA-Z][a-zA-Z0-9-]*[a-zA-Z0-9]).)*([A-Za-z]|[A-Za-z][A-Za-z0-9-]*[A-Za-z0-9])|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))$" required />
          <input name="port" class="form-control input-sm chat-input" value="'.$port.'" placeholder="tcp port" pattern="^([0-9]*)$" required />
          <input name="username" class="form-control input-sm chat-input" value="'.$username.'" placeholder="API username" pattern="^([a-zA-Z0-9]*)$" required />
          <input name="password" type="password" class="form-control input-sm chat-input" placeholder="API password" required />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" name="btn" class="btn btn-primary" value="save">Save changes</button>
        </div>
      </div>
    </div>
  </div>
</form>';
}
?>

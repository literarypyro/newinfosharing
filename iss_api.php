<?php
/* =====================================================================
   iss_api.php  --  REST API for the ISS Monitor mobile app.

   Adds NO queries: every read is produced by the same dash_* function
   the console already uses, so the phone and the workstation cannot
   disagree, and a fix in dash_data.php reaches the app for free.

   ---------------------------------------------------------------------
   RESOURCES

     GET  /days/{date}                    the dashboard payload
     GET  /days/{date}/trains             fleet, one entry per index
     GET  /days/{date}/incidents          the day's register
     GET  /days/{date}/trends[?g=]        breakdowns, severity grid, loops
     GET  /days/{date}/insertions         the day's insertion log

   VOCABULARY: state 'boundary' is labelled "Reserve" -- one state, two
   names (the machine value is the join key, the label is what operations
   says). counts.reserve is that count, so no client has to derive it.
   See iss_state_label().
     GET  /incidents/{id}                 one incident, full record
     GET  /incidents/{id}/annotations     notes and acknowledgements
     POST /incidents/{id}/annotations     add one  (the ONLY write)
     GET  /health                         liveness + coverage

   {date} is YYYY-MM-DD, or the literal 'today'.

   ---------------------------------------------------------------------
   WHY THERE IS NO PUT OR DELETE

   Incidents and train availability are encoded on the desktop console by
   the controller who witnessed them. A phone must not be able to rewrite
   that record -- not because of permissions, but because a CCDR entry is
   an operational log, and a log that can be edited from a handset three
   hours later is not evidence of anything.

   The app therefore appends annotations and nothing else. Adding PUT
   /incidents/{id} later would mean deciding what happens when the phone
   and the console disagree about the same field; today that question
   cannot arise.

   ---------------------------------------------------------------------
   ROUTING

   Prefers PATH_INFO (iss_api.php/days/today/incidents). Falls back to
   ?p=days/today/incidents when the server does not pass PATH_INFO, which
   some IIS and CGI setups do not. Both are supported; the app uses
   PATH_INFO and drops to ?p= only if it gets a 404 on a known route.

   With mod_rewrite you can drop the filename entirely:
     RewriteRule ^api/(.*)$ iss_api.php/$1 [QSA,L]
   ===================================================================== */

require_once(dirname(__FILE__)."/dash_data.php");

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");

/* CORS: only needed if something browser-based ever calls this. The
   Android client does not. Left off deliberately -- turn it on
   explicitly rather than shipping a wildcard. */

/* ---- config ------------------------------------------------------- */
if(!defined('ISS_API_TOKEN'))  define('ISS_API_TOKEN',  '');    /* '' = auth disabled */
if(!defined('ISS_API_WRITES')) define('ISS_API_WRITES', true);  /* false = read-only deployment */

/* =====================================================================
   Response helpers
   ===================================================================== */

/* Every response carries meta.ok.

   ok=false means the READ FAILED. Without it the app cannot tell "no
   incidents today" from "the database is unreachable", and would render
   a calm empty dashboard during an outage -- the exact failure this
   design exists to prevent. The app shows its stale banner and keeps
   the last known values whenever ok is false. */
/* Ops aggregates -- Trends and Insertions. Guarded: a station that has
   not received iss_api_ops.php yet keeps every existing endpoint working
   and gets 'available:false' sections on the new ones. */
if(file_exists(dirname(__FILE__)."/iss_api_ops.php")){
	require_once(dirname(__FILE__)."/iss_api_ops.php");
}

function iss_meta($date=null){
	$m = array(
		'ok'          => dash_ready(),
		'server_time' => date("c"),
		'source'      => 'dash_data.php'
	);
	if($date !== null){
		$m['operating_date'] = $date;
		$m['is_today']       = ($date === date("Y-m-d"));
	}
	/* Rendered verbatim by the app rather than recomposed there. */
	$m['coverage'] = array(
		'continuous_from' => '2025-01',
		'gap_from'        => '2021-06',
		'gap_to'          => '2024-12',
		'note'            => 'Records are continuous from 2025-01. June 2021 to '
		                   . 'December 2024 was not collected — those months are '
		                   . 'absent, not zero.'
	);
	return $m;
}

function iss_send($status, $payload, $date=null){
	http_response_code($status);
	$payload['meta'] = iss_meta($date);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function iss_ok($payload, $date=null){ iss_send(200, $payload, $date); }

function iss_error($status, $code, $message){
	http_response_code($status);
	echo json_encode(array(
		'error' => array('code'=>$code, 'message'=>$message),
		'meta'  => iss_meta()
	), JSON_UNESCAPED_SLASHES);
	exit;
}

/* =====================================================================
   Auth
   ===================================================================== */
function iss_authenticate(){
	if(ISS_API_TOKEN === '') return;   /* disabled */

	$sent = '';
	if(isset($_SERVER['HTTP_X_ISS_TOKEN'])){
		$sent = $_SERVER['HTTP_X_ISS_TOKEN'];
	} else if(isset($_SERVER['HTTP_AUTHORIZATION'])
	       && stripos($_SERVER['HTTP_AUTHORIZATION'],'Bearer ') === 0){
		$sent = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
	}

	$valid = function_exists('hash_equals')
		? hash_equals(ISS_API_TOKEN, $sent)
		: (ISS_API_TOKEN === $sent);

	if(!$valid){
		header('WWW-Authenticate: Bearer realm="iss"');
		iss_error(401, 'unauthorized', 'Missing or invalid API token.');
	}
}
iss_authenticate();

/* =====================================================================
   Routing
   ===================================================================== */
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

$path = '';
if(isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== ''){
	$path = $_SERVER['PATH_INFO'];
} else if(isset($_GET['p'])){
	$path = $_GET['p'];
}
$seg = array_values(array_filter(explode('/', trim((string)$path, '/')), 'strlen'));

/* 'today' is a first-class identifier: the app should not have to know
   the server's timezone to ask for the current operating date. */
function iss_resolve_date($v){
	if($v === 'today' || $v === '' || $v === null) return date("Y-m-d");
	return dash_date($v);
}

/* =====================================================================
   Representations -- the only place the wire format is defined
   ===================================================================== */

/* =====================================================================
   @reserve -- "Reserve" and "boundary" are the same thing.

   dash_state_meta() labels the state "At boundary", because that is the
   physical fact: the set is standing at the boundary. The console
   dashboard shows those same trains under "Reserve", because that is what
   operations CALL them. Two names for one state, and this API was passing
   the internal one through as though it were the user-facing one.

   That put the mobile app in an impossible position. Its Reserve tile had
   no figure to read, so it inferred one from index_type=='reserve' -- a
   field that does not exist on this payload. The tile read 0 every day,
   which is the worst kind of wrong: a confident number for a question
   that was never actually asked.

   So the vocabulary is decided HERE, once:
     - 'state' keeps the machine value 'boundary'. It is the join key
       between this API and dash_data.php, and renaming it would silently
       break every existing client's switch.
     - 'state_label' becomes "Reserve" -- what a supervisor reads.
     - counts gain an explicit 'reserve', so no client derives it again.

   If operations ever splits the two ideas apart -- reserve meaning "held
   back", boundary meaning "standing at the boundary" -- this is the only
   function that needs to change.
   ===================================================================== */
function iss_state_label($state, $fallback){
	if($state === 'boundary') return 'Reserve';
	return $fallback;
}

/* @skipping -- inserted, but not at North Ave., so it did not run the full
   line. Deliberately NOT folded into 'state': the train IS in service, and
   overwriting its state would lose that. Same test as the console. */
function iss_is_skipping($t){
	$to = isset($t['inserted_to']) ? trim((string)$t['inserted_to']) : '';
	return ($to !== '' && $to !== 'North Ave.');
}

function iss_train($t){
	$cars = array();
	foreach(array('car_a','car_b','car_c','car_d') as $k){
		if(isset($t[$k]) && trim((string)$t[$k]) !== '') $cars[] = trim((string)$t[$k]);
	}
	$meta = dash_state_meta($t['state']);
	return array(
		'id'            => isset($t['id']) ? $t['id'] : null,
		'index_no'      => (string)$t['index_no'],
		'state'         => $t['state'],   /* online|boundary|removed|cancelled|pending */
		'state_label'   => iss_state_label($t['state'], $meta[0]),   /* @reserve */
		'tone'          => $t['revenue'] ? $meta[1] : 'mute',
		'revenue'       => (bool)$t['revenue'],
		'type'          => isset($t['type']) ? (string)$t['type'] : '',
		'cars'          => $cars,
		'boundary_time' => dash_hm(isset($t['boundary_time']) ? $t['boundary_time'] : ''),
		'insert_time'   => dash_hm(isset($t['insert_time'])   ? $t['insert_time']   : ''),
		'remove_time'   => dash_hm(isset($t['remove_time'])   ? $t['remove_time']   : ''),
		'inserted_to'   => isset($t['inserted_to'])  ? (string)$t['inserted_to']  : '',
		'removed_from'  => isset($t['removed_from']) ? (string)$t['removed_from'] : '',
		/* @skipping -- train_operations_parallel.php filters on this, and the
		   dashboard badges it on the insertions card. An inserted train whose
		   insertion point is not North Ave. did not run the full line.

		   Sent as its own flag rather than folded into 'state': the train IS
		   in service, and overwriting its state would lose that. The client
		   decides whether to render it as a sixth state or a badge. */
		'skipping'      => iss_is_skipping($t)
	);
}

function iss_incident($r, $full=false){
	$out = array(
		'id'          => dash_incident_id($r),
		'incident_no' => isset($r['incident_no']) ? trim((string)$r['incident_no']) : '',
		'time'        => dash_hm(isset($r['incident_date']) ? $r['incident_date'] : ''),
		'datetime'    => isset($r['incident_date']) ? (string)$r['incident_date'] : '',
		'level'       => $r['lvl'],          /* 'L2'..'L4', or '' */
		'is_failure'  => (bool)$r['failure'],
		'open'        => (bool)$r['open'],
		'tone'        => dash_level_tone($r['lvl']),
		'index_no'    => isset($r['index_no']) ? (string)$r['index_no'] : '',
		'type_label'  => dash_type_label(isset($r['incident_type']) ? $r['incident_type'] : ''),
		/* May be '' -- these columns are probed, not assumed. The client
		   must render the row without them, not print a stray separator. */
		'where'       => dash_incident_where($r),
		'summary'     => dash_incident_text($r, 140)
	);
	if($full){
		$out['description'] = isset($r['description']) ? (string)$r['description'] : '';
		$out['raw']         = $r;   /* everything this schema happens to carry */
	}
	return $out;
}

/* One incident by primary key, via the day-scoped loader when possible. */
function iss_find_incident($id){
	if(!dash_ready() || !dash_table_exists('incident_report')) return null;
	$db  = dash_db();
	$idc = dash_pick_col('incident_report', array('id','incident_id','ir_id'));
	if($idc === '') return null;
	$rs = @$db->query("select * from incident_report where `".$idc."`='".dash_esc($id)."' limit 1");
	if(!$rs || !$rs->num_rows) return null;
	$r = $rs->fetch_assoc();
	$r['lvl']     = dash_level($r);
	$r['failure'] = dash_is_failure($r['lvl']);
	$r['open']    = dash_incident_open($r);
	return $r;
}

/* =====================================================================
   Annotations -- the only writable resource
   ===================================================================== */

/* Append-only, in its own table so nothing the console owns is touched.
   Created on first use; if the CREATE fails the endpoint reports it
   rather than silently accepting writes that go nowhere. */
function iss_annotations_table(){
	static $ready = null;
	if($ready !== null) return $ready;
	if(!dash_ready()) return $ready = false;
	$db = dash_db();
	$ok = @$db->query(
		"create table if not exists iss_annotation (
		   id int unsigned not null auto_increment primary key,
		   incident_id varchar(64) not null,
		   kind varchar(24) not null,
		   author varchar(120) not null,
		   body text null,
		   created_at datetime not null,
		   index ix_incident (incident_id)
		 ) engine=InnoDB default charset=utf8"
	);
	return $ready = (bool)$ok;
}

function iss_annotations($incidentId){
	$out = array();
	if(!iss_annotations_table()) return $out;
	$db = dash_db();
	$rs = @$db->query("select id,kind,author,body,created_at from iss_annotation
	                    where incident_id='".dash_esc($incidentId)."'
	                    order by created_at asc, id asc");
	if($rs){
		while($r = $rs->fetch_assoc()){
			$out[] = array(
				'id'         => (int)$r['id'],
				'kind'       => $r['kind'],
				'author'     => $r['author'],
				'body'       => $r['body'],
				'created_at' => date("c", dash_ts($r['created_at']))
			);
		}
	}
	return $out;
}

function iss_add_annotation($incidentId, $kind, $author, $body){
	if(!iss_annotations_table()) return null;
	$db = dash_db();
	$ok = @$db->query("insert into iss_annotation (incident_id,kind,author,body,created_at)
	                   values ('".dash_esc($incidentId)."','".dash_esc($kind)."',
	                           '".dash_esc($author)."',".
	                           ($body === null ? "null" : "'".dash_esc($body)."'").",
	                           '".date("Y-m-d H:i:s")."')");
	if(!$ok) return null;
	return (int)$db->insert_id;
}

/* Accepts JSON or form-encoded, so curl and the app agree. */
function iss_body(){
	$raw = file_get_contents('php://input');
	if(trim((string)$raw) === '') return $_POST;
	$j = json_decode($raw, true);
	return is_array($j) ? $j : $_POST;
}

/* =====================================================================
   Dispatch
   ===================================================================== */

if(!count($seg)) $seg = array('health');

/* ---- /health ------------------------------------------------------ */
if($seg[0] === 'health'){
	if($method !== 'GET') iss_error(405, 'method_not_allowed', 'GET only.');
	iss_ok(array(
		'tables' => array(
			'train_availability' => dash_table_exists('train_availability'),
			'train_ava_time'     => dash_table_exists('train_ava_time'),
			'incident_report'    => dash_table_exists('incident_report'),
			'equipment_type'     => dash_table_exists('equipment_type'),
			'timetable_day'      => dash_table_exists('timetable_day'),
			'train_compo'        => dash_table_exists('train_compo'),
			'level_condition'    => function_exists('iss_column_exists')
			                        && iss_column_exists('incident_report','level_condition'),
			'index_switch'       => dash_switch_table(),
			'iss_annotation'     => iss_annotations_table()
		),
		'writes_enabled' => (bool)ISS_API_WRITES
	));
}

/* ---- /days/{date}[/sub] ------------------------------------------- */
if($seg[0] === 'days'){
	if($method !== 'GET') iss_error(405, 'method_not_allowed', 'GET only.');
	if(!isset($seg[1])) iss_error(400, 'bad_request', 'A date is required: /days/today');

	$date = iss_resolve_date($seg[1]);
	$sub  = isset($seg[2]) ? $seg[2] : '';

	if($sub === 'trains'){
		$rows = array();
		$c = dash_fleet_counts($date);
		$rsv = 0; $skp = 0;
		foreach(dash_trains($date) as $t){
			$rows[] = iss_train($t);
			if($t['state'] === 'boundary') $rsv++;
			if($t['state'] === 'online' && iss_is_skipping($t)) $skp++;
		}
		$c['reserve']  = $rsv;    /* @reserve -- same derivation as /today */
		$c['skipping'] = $skp;
		iss_ok(array(
			'counts'   => $c,
			'trains'   => $rows,
			'switches' => dash_switches($date)
		), $date);
	}

	if($sub === 'incidents'){
		$rows = array();
		foreach(dash_incidents($date) as $r) $rows[] = iss_incident($r);
		iss_ok(array(
			'counts'    => dash_incident_counts($date),
			'incidents' => $rows
		), $date);
	}

	if($sub === 'insertions'){
		iss_ok(array(
			'insertions' => function_exists('iss_insertions') ? iss_insertions($date) : array()
		), $date);
	}

	if($sub === 'trends'){
		/* @ops -- The operational aggregates the mobile Trends screen reads:
		   severity grid, AM/PM cancellations, loop completion, LRV count and
		   the bucketed trend. Merged into this payload rather than given their
		   own endpoint so the screen still costs ONE request.

		   ?g= picks the trend bucket size, the same parameter the console's
		   dashboard uses. Absent means monthly. */
		if(function_exists('iss_ops_trends')){
			$ops = iss_ops_trends($date, isset($_GET['g']) ? $_GET['g'] : 'month');
			iss_ok(array(
				'severity' => $ops['severity'],
				'ampm'     => $ops['ampm'],
				'loops'    => $ops['loops'],
				'lrv'      => $ops['lrv'],
				'trend'    => $ops['trend'],
				'types'    => $ops['types'],
				'months'   => dash_month_series($date, DASH_TREND_MONTHS),
				'sparks'   => array(
					'trains'    => dash_spark_trains($date),
					'incidents' => dash_spark_incidents($date),
					'cancelled' => dash_spark_cancelled($date)
				)
			), $date);
		}

		/* Fallback: iss_api_ops.php not installed. The old payload, so the
		   app's problem-type card and sparklines keep working. */
		iss_ok(array(
			'types'  => dash_type_breakdown($date, 8),
			'months' => dash_month_series($date, DASH_TREND_MONTHS),
			'sparks' => array(
				'trains'    => dash_spark_trains($date),
				'incidents' => dash_spark_incidents($date),
				'cancelled' => dash_spark_cancelled($date)
			),
			'deltas' => array(
				'trains'    => dash_delta(dash_spark_trains($date),    $date),
				'incidents' => dash_delta(dash_spark_incidents($date), $date),
				'cancelled' => dash_delta(dash_spark_cancelled($date), $date)
			)
		), $date);
	}

	if($sub !== '') iss_error(404, 'not_found', 'Unknown sub-resource: '.$sub);

	/* the day itself -- what the dashboard screen needs in one call */
	$band = array();
	foreach(dash_band_items($date) as $it){
		$band[] = array(
			'id'    => $it['id'],
			'no'    => $it['no'],
			'time'  => $it['ts'] ? date("H:i", $it['ts']) : '',
			'level' => $it['lvl'],
			'tone'  => dash_level_tone($it['lvl']),
			'open'  => (bool)$it['open'],
			'text'  => $it['text']
		);
	}
	$recent = array(); $n = 0;
	foreach(dash_incidents($date) as $r){
		if($n++ >= 5) break;
		$recent[] = iss_incident($r);
	}
	$strip = array();
	foreach(dash_trains($date) as $t){
		$m = dash_state_meta($t['state']);
		$strip[] = array(
			'index_no'    => (string)$t['index_no'],
			'state'       => $t['state'],
			/* @reserve -- the strip carried state+tone only, so the Today
			   tiles had nothing to count Skipping or Reserve from and fell
			   back to a field that does not exist here. Both travel now. */
			'state_label' => iss_state_label($t['state'], $m[0]),
			'skipping'    => iss_is_skipping($t),
			'tone'        => $t['revenue'] ? $m[1] : 'mute'
		);
	}
	/* @reserve -- Both figures come from the SAME dash_trains() pass the
	   strip is built from, so the tiles and the strip can never disagree
	   about how many sets are held in reserve. */
	$fleet = dash_fleet_counts($date);
	$reserve = 0; $skipping = 0;
	foreach(dash_trains($date) as $t){
		if($t['state'] === 'boundary') $reserve++;
		if($t['state'] === 'online' && iss_is_skipping($t)) $skipping++;
	}
	$fleet['reserve']  = $reserve;   /* === boundary, named as operations names it */
	$fleet['skipping'] = $skipping;

	iss_ok(array(
		'fleet'      => $fleet,
		'incidents'  => dash_incident_counts($date),
		'band'       => $band,
		'recent'     => $recent,
		'strip'      => $strip,
		'insertions' => dash_recent_insertions($date, 5),
		'deltas'     => array(
			'trains'    => dash_delta(dash_spark_trains($date),    $date),
			'incidents' => dash_delta(dash_spark_incidents($date), $date),
			'cancelled' => dash_delta(dash_spark_cancelled($date), $date)
		)
	), $date);
}

/* ---- /incidents/{id}[/annotations] -------------------------------- */
if($seg[0] === 'incidents'){
	if(!isset($seg[1])){
		iss_error(400, 'bad_request',
			'An incident id is required. For a day\'s register use /days/{date}/incidents.');
	}
	$id  = $seg[1];
	$sub = isset($seg[2]) ? $seg[2] : '';

	if($sub === 'annotations'){
		if($method === 'GET'){
			iss_ok(array('incident_id'=>$id, 'annotations'=>iss_annotations($id)));
		}
		if($method === 'POST'){
			if(!ISS_API_WRITES){
				iss_error(403, 'writes_disabled', 'This deployment is read-only.');
			}
			$in     = iss_body();
			$kind   = isset($in['kind'])   ? trim((string)$in['kind'])   : 'note';
			$author = isset($in['author']) ? trim((string)$in['author']) : '';
			$body   = isset($in['body'])   ? trim((string)$in['body'])   : '';

			if(!in_array($kind, array('note','acknowledge'), true)){
				iss_error(422, 'invalid_kind', "kind must be 'note' or 'acknowledge'.");
			}
			if($author === ''){
				iss_error(422, 'author_required', 'author is required — an unattributed note on an operational log is worthless.');
			}
			if($kind === 'note' && $body === ''){
				iss_error(422, 'body_required', 'A note needs a body.');
			}
			if(iss_find_incident($id) === null){
				iss_error(404, 'not_found', 'No incident with id '.$id);
			}

			$newId = iss_add_annotation($id, $kind, $author, $kind === 'acknowledge' ? null : $body);
			if($newId === null){
				iss_error(500, 'write_failed', 'Could not store the annotation.');
			}

			header('Location: '.$_SERVER['SCRIPT_NAME'].'/incidents/'.rawurlencode($id).'/annotations');
			iss_send(201, array(
				'created'     => true,
				'id'          => $newId,
				'incident_id' => $id,
				'annotations' => iss_annotations($id)
			));
		}
		iss_error(405, 'method_not_allowed', 'GET or POST only.');
	}

	if($sub !== '') iss_error(404, 'not_found', 'Unknown sub-resource: '.$sub);
	if($method !== 'GET') iss_error(405, 'method_not_allowed',
		'Incident records are encoded on the console and are read-only here.');

	$r = iss_find_incident($id);
	if($r === null) iss_error(404, 'not_found', 'No incident with id '.$id);

	$out = iss_incident($r, true);
	$out['annotations'] = iss_annotations($id);
	iss_ok(array('incident' => $out));
}

iss_error(404, 'not_found', 'Unknown resource: /'.implode('/', $seg));

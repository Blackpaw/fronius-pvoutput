<?php

// Constants
$pvOutputApiURL = "http://pvoutput.org/service/r2/addstatus.jsp?";

// Configuration Options
$dataManagerIP = "";
$pvOutputApiKEY = "";
$pvOutputSID = "";
$weatherStationURL = null;
$logToCSV = false;

// Default to Brisbane
$pvTimeszone = "Australia/Brisbane";

// Define Date & Time
date_default_timezone_set($pvTimeszone);
$system_time= time();
$date = date('Ymd', time());
$time = date('H:i', time());

// Load config if exists (sets above options)
$configFile = dirname(__FILE__)  . DIRECTORY_SEPARATOR . 'config.php';
echo "Config file = $configFile\n";
if (file_exists($configFile))
	include $configFile;

// Data file
$dataFile = dirname(__FILE__)  . DIRECTORY_SEPARATOR . 'fronius.dat';
echo "Data file = $dataFile\n";

// CSV file
$csvFile = dirname(__FILE__)  . DIRECTORY_SEPARATOR . "fronius-$date.csv";
echo "CSV file = $csvFile\n";

// Inverter & Smart Meter API URLs
$inverterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetPowerFlowRealtimeData.fcgi";
$meterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetMeterRealtimeData.cgi?Scope=Device&DeviceId=0";


print "Inverter URL = " . "<" . $inverterDataURL . ">\n";
print "Meter URL = " . "<" . $meterDataURL . ">\n";

// Predeclare our variables
$consumptionEnergyTotal = NULL;
$consumptionPowerLive = NULL;
$inverterVoltageLive = NULL;
$inverterEnergyTotal = NULL;
$inverterPowerLive = NULL;
$temperature = null;


// Read Weather Data
$weatherJSON = file_get_contents($weatherStationURL);
if ($weatherJSON)
{
	$weatherData = json_decode($weatherJSON, true);
	$temperature = $weatherData["observations"]["data"]["0"]["air_temp"];
	print "Temparature = " . $temperature . "c\n";
};


// Get 1-Based Day Of Year (Currently unused)
$DOY = date('z') + 1;

// Get timestamp
$timeStamp = time();

// Read Meter Data
$meterJSON = file_get_contents($meterDataURL);
if ($meterJSON)
{
	$meterData = json_decode($meterJSON, true);
	$consumptionEnergyTotal = $meterData["Body"]["Data"]["EnergyReal_WAC_Sum_Consumed"];
	$inverterVoltageLive = $meterData["Body"]["Data"]["Voltage_AC_Phase_1"];
}


// Read Inverter Data
$inverterJSON = file_get_contents($inverterDataURL);
$inverterData = json_decode($inverterJSON, true);
$inverterEnergyTotal = $inverterData["Body"]["Data"]["Site"]["E_Total"];


////////////////////////////////////////
// Read previous data to calculate avg power produced/consumed
if (file_exists($dataFile))
{
	$readData = unserialize(file_get_contents($dataFile));
	$prevTimestamp = $readData['timestamp'];
	$prevInverterEnergyTotal = $readData['v1'];
	$prevConsumptionEnergyTotal = $readData['v3'];

	// Sanity Checks
	if ($prevInverterEnergyTotal == null)
		$prevInverterEnergyTotal = 0;
	if ($prevConsumptionEnergyTotal == null)
		$prevConsumptionEnergyTotal = 0;

	$span = $timeStamp - $prevTimestamp;
	if ($span > 0) // should always be true
	{
		// Avg Producion Level
		$inverterPowerLive = ($inverterEnergyTotal - $prevInverterEnergyTotal) / $span  * 60 * 60;	

		if ($consumptionEnergyTotal != null)
		{
			// Avg Consumption Level
			$consumptionPowerLive = ($consumptionEnergyTotal - $prevConsumptionEnergyTotal) / $span  * 60 * 60;	
		}
	}
}


///////////////////////////////////////////////
// Print Values to Console
Echo "\n";
Echo "d \t $date\n";
Echo "t \t $time\n";
Echo "v1 \t $inverterEnergyTotal\n";
Echo "v2 \t $inverterPowerLive\n";
Echo "v3 \t $consumptionEnergyTotal\n";
Echo "v4 \t $consumptionPowerLive\n";
Echo "v5 \t $temperature\n";
Echo "v6 \t $inverterVoltageLive\n";

///////////////////////////////////////////////
// Push to PVOutput
$pvOutputURL = $pvOutputApiURL
                . "key=" .  $pvOutputApiKEY
                . "&sid=" . $pvOutputSID
                . "&d=" .   $date
                . "&t=" .   $time
                . "&v1=" .  $inverterEnergyTotal
                . "&c1=1";
if ($inverterVoltageLive != null)
    $pvOutputURL = "$pvOutputURL&v6=$inverterVoltageLive";
if ($consumptionEnergyTotal != null)
	$pvOutputURL = "$pvOutputURL&v3=$consumptionEnergyTotal";
if ($temperature != null)
	$pvOutputURL = "$pvOutputURL&v5=$temperature";
if ($inverterPowerLive != null)
	$pvOutputURL = "$pvOutputURL&v2=$inverterPowerLive";
if ($consumptionPowerLive != null)
	$pvOutputURL = "$pvOutputURL&v4=$consumptionPowerLive";
                

Echo "Sending data to PVOutput.org \n";
Echo "$pvOutputURL \n";
Echo "\n";

$context = stream_context_create(array(
    'http' => array(
        'ignore_errors' => true
     )
));

$rc_ok = "OK 200";
$rc = file_get_contents(trim($pvOutputURL), false, $context);
print "result = " . $rc . "\n";
if (substr($rc, 0, strlen($rc_ok)) !== $rc_ok)
    echo "Error posting data: $rc\n";
{
	// Save Current Data
	$saveData = serialize(array('DOY' => $DOY, 'timestamp' => $timeStamp, 'v1' => $inverterEnergyTotal, 'v3' => $consumptionEnergyTotal));
    	file_put_contents($dataFile, $saveData);
}

if ($logToCSV)
{
	// Append to CSV File
	if (! file_exists($csvFile))
		file_put_contents($csvFile, "time,v1,v2,v3,v4,v5,v6\n");
	file_put_contents($csvFile, "$time,$inverterEnergyTotal,$inverterPowerLive,$consumptionEnergyTotal,$consumptionPowerLive,$temperature,$inverterVoltageLive\n", FILE_APPEND);
}
?>




# fronius-pvoutput
Simple PHP Script for reading the Fronius inverter/meter values and pushing to pvputput.org

Uploads data from Fronius Solar Inverter to PVOutput.org. If you have a Fronius Smart Meter it will upload consumption data as well. Live Power and consumption values
will be averaged between calls, avoiding anomalous spikes.

If a BOM weather station JSON url is provided, the current temperature will be posted as well (Australia only).
The JSON url for the weather data can be got by:
* Going to the weather station page for your area
  eg http://www.bom.gov.au/products/IDQ60901/IDQ60901.94575.shtml
* Click on "Other Formats" (near the top right)
* Copy the "JavaScript Object Notation format (JSON) in row-major order" link.
* If desired, test it in a browser, you should get a nicely formatted json result.

Inspired by:
* https://github.com/b33st/Fronius_PVOutput_Uploader
* https://shkspr.mobi/blog/2014/11/fronius-and-pvoutput/

Thanks to:
* bankstownbloke
* bulletmark

# Configuration
At a minimun the
* `$dataManagerIP`
**  IP of your Fronius Inverter
* `$pvOutputApiKEY`
**  API Key from pvoutput.org
* `$pvOutputSID`
**  Site Id from pvoutput.org

Need to be set

The fronius.php script can be edited directly, but better to create a "config.php" file in the same directory as the script, this will be included automatically. A sample is displayed below with dummy values.

```php
<?php
$dataManagerIP = "192.168.0.112";
$pvOutputApiKEY = "6eecca0f-e6f5-47d4-bc55-281e98f75e01";
$pvOutputSID = "12345";
// Archerfield
$weatherStationURL = "http://reg.bom.gov.au/fwo/IDQ60901/IDQ60901.94575.json";
$pvTimeszone = "Australia/Brisbane";
?>
```

The script should be invoked every 5 minutes - cron is perfect for this (on a linux system). A example crontab entry below.

`*/5 * * * * /usr/bin/php /home/user1/fronius/fronius.php > /dev/null`




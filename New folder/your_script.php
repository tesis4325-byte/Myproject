<?php
/**
 * GPS Trip Processor
 * 
 * Processes a CSV file containing GPS points and generates trips with statistics.
 * 
 * Usage: php your_script.php
 */

// Configuration
define('INPUT_FILE', 'points.csv');
define('REJECTS_LOG', 'rejects.log');
define('OUTPUT_FILE', 'trips.geojson');
define('MAX_TIME_GAP', 25 * 60); // 25 minutes in seconds
define('MAX_DISTANCE_GAP', 2); // 2 kilometers

/**
 * Calculate the distance between two points using the Haversine formula
 * 
 * @param float $lat1 Latitude of point 1
 * @param float $lon1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lon2 Longitude of point 2
 * @return float Distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    
    $deltaLat = $lat2Rad - $lat1Rad;
    $deltaLon = $lon2Rad - $lon1Rad;
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Validate GPS coordinates
 * 
 * @param float $lat Latitude
 * @param float $lon Longitude
 * @return bool True if valid, false otherwise
 */
function isValidCoordinate($lat, $lon) {
    return ($lat >= -90 && $lat <= 90) && ($lon >= -180 && $lon <= 180);
}

/**
 * Validate timestamp
 * 
 * @param string $timestamp Timestamp string
 * @return bool True if valid, false otherwise
 */
function isValidTimestamp($timestamp) {
    try {
        new DateTime($timestamp);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calculate speed between two points in km/h
 * 
 * @param float $distance Distance in kilometers
 * @param int $timeDiff Time difference in seconds
 * @return float Speed in km/h
 */
function calculateSpeed($distance, $timeDiff) {
    if ($timeDiff <= 0) return 0;
    return ($distance / $timeDiff) * 3600; // Convert to km/h
}

// Initialize
$points = [];
$rejectedRows = [];
$output = [
    'type' => 'FeatureCollection',
    'features' => []
];

// Open rejects log file
$rejectsLog = fopen(REJECTS_LOG, 'w');
if (!$rejectsLog) {
    die('Could not open rejects log file for writing');
}

// Read CSV file
if (!file_exists(INPUT_FILE)) {
    die('Input file ' . INPUT_FILE . ' not found');
}

$file = fopen(INPUT_FILE, 'r');
if (!$file) {
    die('Could not open input file for reading');
}

// Read header
$header = fgetcsv($file);
if (!$header) {
    die('Could not read header from CSV file');
}

// Process each row
$rowNumber = 1;
while (($row = fgetcsv($file)) !== false) {
    $rowNumber++;
    
    // Check if row has correct number of columns
    if (count($row) != 4) {
        fwrite($rejectsLog, "Row $rowNumber: Invalid number of columns\n");
        continue;
    }
    
    // Extract data
    [$device_id, $lat, $lon, $timestamp] = $row;
    
    // Validate data
    if (!is_numeric($lat) || !is_numeric($lon)) {
        fwrite($rejectsLog, "Row $rowNumber: Invalid coordinates ($lat, $lon)\n");
        continue;
    }
    
    $lat = (float)$lat;
    $lon = (float)$lon;
    
    if (!isValidCoordinate($lat, $lon)) {
        fwrite($rejectsLog, "Row $rowNumber: Coordinates out of range ($lat, $lon)\n");
        continue;
    }
    
    if (!isValidTimestamp($timestamp)) {
        fwrite($rejectsLog, "Row $rowNumber: Invalid timestamp ($timestamp)\n");
        continue;
    }
    
    // Parse timestamp
    try {
        $dateTime = new DateTime($timestamp);
    } catch (Exception $e) {
        fwrite($rejectsLog, "Row $rowNumber: Could not parse timestamp ($timestamp)\n");
        continue;
    }
    
    // Add valid point
    $points[] = [
        'device_id' => $device_id,
        'lat' => $lat,
        'lon' => $lon,
        'timestamp' => $timestamp,
        'datetime' => $dateTime,
        'timestamp_sec' => $dateTime->getTimestamp()
    ];
}

fclose($file);
fclose($rejectsLog);

// Sort points by timestamp
usort($points, function($a, $b) {
    return $a['timestamp_sec'] - $b['timestamp_sec'];
});

// Split into trips
$trips = [];
$currentTrip = [];
$tripNumber = 1;

for ($i = 0; $i < count($points); $i++) {
    $point = $points[$i];
    
    // Start new trip if this is the first point or if conditions are met
    if (empty($currentTrip)) {
        $currentTrip[] = $point;
        continue;
    }
    
    // Get previous point
    $prevPoint = end($currentTrip);
    
    // Check time gap
    $timeDiff = $point['timestamp_sec'] - $prevPoint['timestamp_sec'];
    
    // Check distance
    $distance = calculateDistance(
        $prevPoint['lat'], 
        $prevPoint['lon'], 
        $point['lat'], 
        $point['lon']
    );
    
    // Start new trip if time gap > MAX_TIME_GAP or distance > MAX_DISTANCE_GAP
    if ($timeDiff > MAX_TIME_GAP || $distance > MAX_DISTANCE_GAP) {
        // Save current trip
        if (count($currentTrip) > 1) { // Only save trips with more than one point
            $trips[] = $currentTrip;
        }
        
        // Start new trip
        $currentTrip = [$point];
        $tripNumber++;
    } else {
        // Continue current trip
        $currentTrip[] = $point;
    }
}

// Add last trip if it has more than one point
if (count($currentTrip) > 1) {
    $trips[] = $currentTrip;
}

// Process each trip to calculate statistics and create GeoJSON
$colors = [
    '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', 
    '#00FFFF', '#FFA500', '#800080', '#008000', '#FFC0CB',
    '#A52A2A', '#000080', '#808080', '#00FF7F', '#FFD700'
];

foreach ($trips as $tripIndex => $trip) {
    // Calculate trip statistics
    $totalDistance = 0;
    $maxSpeed = 0;
    $coordinates = [];
    
    // Collect coordinates for GeoJSON
    foreach ($trip as $point) {
        $coordinates[] = [$point['lon'], $point['lat']]; // GeoJSON uses [longitude, latitude]
    }
    
    // Calculate distances and speeds between consecutive points
    $speeds = [];
    for ($i = 1; $i < count($trip); $i++) {
        $prevPoint = $trip[$i - 1];
        $currPoint = $trip[$i];
        
        $timeDiff = $currPoint['timestamp_sec'] - $prevPoint['timestamp_sec'];
        $distance = calculateDistance(
            $prevPoint['lat'], 
            $prevPoint['lon'], 
            $currPoint['lat'], 
            $currPoint['lon']
        );
        
        $totalDistance += $distance;
        
        if ($timeDiff > 0) {
            $speed = calculateSpeed($distance, $timeDiff);
            $speeds[] = $speed;
            if ($speed > $maxSpeed) {
                $maxSpeed = $speed;
            }
        }
    }
    
    // Calculate duration in minutes
    $firstPoint = $trip[0];
    $lastPoint = end($trip);
    $durationMinutes = ($lastPoint['timestamp_sec'] - $firstPoint['timestamp_sec']) / 60;
    
    // Calculate average speed in km/h
    $avgSpeed = 0;
    if ($durationMinutes > 0) {
        $avgSpeed = ($totalDistance / $durationMinutes) * 60; // Convert to km/h
    }
    
    // Get color for this trip
    $colorIndex = $tripIndex % count($colors);
    $color = $colors[$colorIndex];
    
    // Create GeoJSON feature
    $feature = [
        'type' => 'Feature',
        'properties' => [
            'trip_id' => 'trip_' . ($tripIndex + 1),
            'total_distance_km' => round($totalDistance, 3),
            'duration_minutes' => round($durationMinutes, 2),
            'average_speed_kmh' => round($avgSpeed, 2),
            'max_speed_kmh' => round($maxSpeed, 2),
            'color' => $color
        ],
        'geometry' => [
            'type' => 'LineString',
            'coordinates' => $coordinates
        ]
    ];
    
    $output['features'][] = $feature;
}

// Write GeoJSON output
$geojson = json_encode($output, JSON_PRETTY_PRINT);
if (file_put_contents(OUTPUT_FILE, $geojson) === false) {
    die('Could not write GeoJSON output file');
}

echo "Processing complete!\n";
echo "Rejected rows logged to: " . REJECTS_LOG . "\n";
echo "GeoJSON output written to: " . OUTPUT_FILE . "\n";
echo "Number of trips generated: " . count($trips) . "\n";
?>
# GPS Trip Processor

This PHP script processes a CSV file containing GPS points and generates trips with statistics in GeoJSON format.

## Requirements

- PHP 8.0 or higher

## Files

- `your_script.php` - Main processing script
- `points.csv` - Input CSV file with GPS points
- `rejects.log` - Log file for rejected/invalid rows
- `trips.geojson` - Output GeoJSON file (generated when script runs)

## Usage

1. Ensure you have PHP 8.0+ installed
2. Place your GPS data in a file named `points.csv` in the same directory
3. Run the script:

```bash
php your_script.php
```

## CSV Format

The input CSV file should have the following columns:
- `device_id` (string): Device identifier
- `lat` (decimal degrees): Latitude (-90 to 90)
- `lon` (decimal degrees): Longitude (-180 to 180)
- `timestamp` (ISO 8601): Timestamp in UTC

Example:
```csv
device_id,lat,lon,timestamp
device1,39.9042,116.4074,2023-01-01T10:00:00Z
device2,40.7128,-74.0060,2023-01-01T11:00:00Z
```

## Processing Logic

1. **Data Cleaning**
   - Invalid coordinates (lat not between -90 and 90, lon not between -180 and 180) are rejected
   - Invalid timestamps are rejected
   - Rejected rows are logged to `rejects.log`

2. **Trip Splitting**
   - A new trip is started if:
     - Time gap between consecutive points is greater than 25 minutes
     - Straight-line distance between consecutive points is more than 2 km

3. **Statistics Calculation**
   - Total distance (kilometers)
   - Duration (minutes)
   - Average speed (km/h)
   - Maximum speed (km/h)

4. **Output**
   - GeoJSON FeatureCollection with each trip as a LineString
   - Each trip has a different color property
<?php
/**
 * Search Professionals API
 * Returns professionals matching search criteria
 * 
 * Expected parameters:
 * - query: Service to search for (plumbing, electrical, etc.)
 * - zipcode: Zip code to search near (required)
 * - maxDistance: Maximum distance in miles (default: 50)
 * 
 * Returns JSON in format:
 * {
 *   "success": true,
 *   "count": 8,
 *   "results": {
 *     "near": [...],
 *     "medium": [...],
 *     "far": [...],
 *     "distant": [...]
 *   }
 * }
 */

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set JSON headers with CORS support
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Start output buffering to prevent premature output
ob_start();

// Get and sanitize parameters from URL
$query = isset($_GET['query']) ? trim(htmlspecialchars($_GET['query'])) : '';
$zipcode = isset($_GET['zipcode']) ? trim(htmlspecialchars($_GET['zipcode'])) : '';
$maxDistance = isset($_GET['maxDistance']) ? intval($_GET['maxDistance']) : 50;

// Log the request for debugging
error_log("Search API called: query='$query', zipcode='$zipcode', maxDistance=$maxDistance");

// Validate required parameters
if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Valid zip code is required (5-digit US zip code)',
        'code' => 'INVALID_ZIP'
    ]);
    ob_end_flush();
    exit();
}

try {
    // ============================================
    // SAMPLE DATA - REPLACE WITH DATABASE QUERY
    // ============================================
    // This is temporary sample data. Replace with actual database query.
    
    // Create comprehensive sample professionals
    $sampleProfessionals = [
        // Near (0-10 miles)
        [
            'id' => 1,
            'full_name' => 'John Smith',
            'username' => 'johnsmith',
            'title' => 'Licensed Plumber',
            'city' => 'Beverly Hills',
            'state' => 'CA',
            'distance' => 3.5,
            'rating' => 4.8,
            'review_count' => 47,
            'experience_years' => 15,
            'hourly_rate' => 85.00,
            'specialties' => 'Plumbing, Pipe Repair, Water Heaters, Drain Cleaning',
            'availability' => 'Within 1 week',
            'verified' => true,
            'languages' => ['English', 'Spanish'],
            'response_time' => '1-2 hours',
            'insurance_verified' => true
        ],
        [
            'id' => 2,
            'full_name' => 'Maria Garcia',
            'username' => 'mariag',
            'title' => 'Master Electrician',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'distance' => 7.2,
            'rating' => 4.9,
            'review_count' => 62,
            'experience_years' => 12,
            'hourly_rate' => 95.00,
            'specialties' => 'Electrical Wiring, Panel Upgrades, Lighting Installation',
            'availability' => 'Urgent (24-48 hrs)',
            'verified' => true,
            'languages' => ['English', 'Spanish'],
            'response_time' => 'Under 1 hour',
            'insurance_verified' => true
        ],
        [
            'id' => 3,
            'full_name' => 'Alex Johnson',
            'username' => 'alexj',
            'title' => 'HVAC Technician',
            'city' => 'West Hollywood',
            'state' => 'CA',
            'distance' => 8.1,
            'rating' => 4.6,
            'review_count' => 28,
            'experience_years' => 8,
            'hourly_rate' => 75.00,
            'specialties' => 'Air Conditioning, Heating Repair, HVAC Maintenance',
            'availability' => 'Within 1 week',
            'verified' => true,
            'languages' => ['English'],
            'response_time' => '2-4 hours',
            'insurance_verified' => true
        ],
        
        // Medium (11-25 miles)
        [
            'id' => 4,
            'full_name' => 'Robert Chen',
            'username' => 'robchen',
            'title' => 'General Contractor',
            'city' => 'Santa Monica',
            'state' => 'CA',
            'distance' => 18.5,
            'rating' => 4.7,
            'review_count' => 89,
            'experience_years' => 20,
            'hourly_rate' => 120.00,
            'specialties' => 'Home Renovation, Construction, Kitchen Remodeling',
            'availability' => 'Within 1 month',
            'verified' => true,
            'languages' => ['English', 'Mandarin'],
            'response_time' => '4-8 hours',
            'insurance_verified' => true
        ],
        [
            'id' => 5,
            'full_name' => 'Sarah Williams',
            'username' => 'sarahw',
            'title' => 'Appliance Repair Specialist',
            'city' => 'Culver City',
            'state' => 'CA',
            'distance' => 22.3,
            'rating' => 4.5,
            'review_count' => 41,
            'experience_years' => 10,
            'hourly_rate' => 65.00,
            'specialties' => 'Appliance Repair, Refrigerator, Washer/Dryer',
            'availability' => 'Within 1 week',
            'verified' => true,
            'languages' => ['English'],
            'response_time' => '1-2 hours',
            'insurance_verified' => true
        ],
        
        // Far (26-50 miles)
        [
            'id' => 6,
            'full_name' => 'Michael Brown',
            'username' => 'mikeb',
            'title' => 'Roofing Contractor',
            'city' => 'Long Beach',
            'state' => 'CA',
            'distance' => 32.1,
            'rating' => 4.8,
            'review_count' => 56,
            'experience_years' => 15,
            'hourly_rate' => 110.00,
            'specialties' => 'Roof Repair, Roof Installation, Gutter Cleaning',
            'availability' => 'Within 1 month',
            'verified' => true,
            'languages' => ['English'],
            'response_time' => '24-48 hours',
            'insurance_verified' => true
        ],
        
        // Distant (51+ miles)
        [
            'id' => 7,
            'full_name' => 'David Lee',
            'username' => 'davidl',
            'title' => 'Technology Consultant',
            'city' => 'San Diego',
            'state' => 'CA',
            'distance' => 125.3,
            'rating' => 4.9,
            'review_count' => 105,
            'experience_years' => 10,
            'hourly_rate' => 150.00,
            'specialties' => 'IT Support, Network Setup, Smart Home Installation',
            'availability' => 'Within 1 month',
            'verified' => true,
            'languages' => ['English', 'Korean'],
            'response_time' => '24-48 hours',
            'insurance_verified' => true
        ],
        [
            'id' => 8,
            'full_name' => 'Jennifer Park',
            'username' => 'jenniferp',
            'title' => 'Interior Designer',
            'city' => 'Irvine',
            'state' => 'CA',
            'distance' => 45.7,
            'rating' => 4.7,
            'review_count' => 73,
            'experience_years' => 7,
            'hourly_rate' => 90.00,
            'specialties' => 'Interior Design, Space Planning, Furniture Selection',
            'availability' => 'Within 1 week',
            'verified' => true,
            'languages' => ['English', 'Korean'],
            'response_time' => '2-4 hours',
            'insurance_verified' => true
        ]
    ];
    
    // TEMPORARY: For testing, ignore query filtering and return ALL professionals
    // Remove or comment out this line when you want actual query-based filtering
    $query = '';
    
    // Filter by query if provided
    if (!empty($query)) {
        $queryLower = strtolower($query);
        
        $filteredProfessionals = array_filter($sampleProfessionals, function($pro) use ($queryLower) {
            $specialtiesLower = strtolower($pro['specialties']);
            $titleLower = strtolower($pro['title']);
            $fullNameLower = strtolower($pro['full_name']);
            
            // Check multiple fields for matches
            $matchInSpecialties = strpos($specialtiesLower, $queryLower) !== false;
            $matchInTitle = strpos($titleLower, $queryLower) !== false;
            $matchInName = strpos($fullNameLower, $queryLower) !== false;
            
            // Also check for related terms
            $relatedTerms = [];
            if ($queryLower === 'plumbing') {
                $relatedTerms = ['pipe', 'water', 'drain', 'fixture', 'sewer'];
            } elseif ($queryLower === 'electrical') {
                $relatedTerms = ['wiring', 'lighting', 'panel', 'outlet', 'circuit'];
            } elseif ($queryLower === 'hvac') {
                $relatedTerms = ['heating', 'air conditioning', 'ventilation', 'ac', 'furnace'];
            } elseif ($queryLower === 'contractor') {
                $relatedTerms = ['construction', 'remodeling', 'renovation', 'builder'];
            }
            
            foreach ($relatedTerms as $term) {
                if (strpos($specialtiesLower, $term) !== false || 
                    strpos($titleLower, $term) !== false) {
                    return true;
                }
            }
            
            return $matchInSpecialties || $matchInTitle || $matchInName;
        });
        
        // Re-index array
        $sampleProfessionals = array_values($filteredProfessionals);
    }
    // If query is empty, show all professionals
    
    // Filter by max distance
    if ($maxDistance !== 'all' && is_numeric($maxDistance)) {
        $sampleProfessionals = array_filter($sampleProfessionals, function($pro) use ($maxDistance) {
            return $pro['distance'] <= $maxDistance;
        });
        
        // Re-index array
        $sampleProfessionals = array_values($sampleProfessionals);
    }
    
    // Group professionals by distance
    $groupedResults = [
        'near' => [],
        'medium' => [],
        'far' => [],
        'distant' => []
    ];
    
    foreach ($sampleProfessionals as $professional) {
        $distance = $professional['distance'];
        
        if ($distance <= 10) {
            $groupedResults['near'][] = $professional;
        } elseif ($distance <= 25) {
            $groupedResults['medium'][] = $professional;
        } elseif ($distance <= 50) {
            $groupedResults['far'][] = $professional;
        } else {
            $groupedResults['distant'][] = $professional;
        }
    }
    
    // Calculate total count
    $totalCount = count($groupedResults['near']) + 
                  count($groupedResults['medium']) + 
                  count($groupedResults['far']) + 
                  count($groupedResults['distant']);
    
    // Prepare final response
    $response = [
        'success' => true,
        'count' => $totalCount,
        'parameters' => [
            'query' => $query,
            'zipcode' => $zipcode,
            'maxDistance' => $maxDistance,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'results' => $groupedResults,
        'metadata' => [
            'version' => '1.0',
            'generated_in' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's',
            'note' => 'Using sample data - connect to database for real results'
        ]
    ];
    
    // Send JSON response
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Handle any unexpected errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}

// End output buffering and send response
ob_end_flush();
exit();
?>

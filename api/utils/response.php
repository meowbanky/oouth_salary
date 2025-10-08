<?php
/**
 * API Response Handler
 * Standardized response formatting for JSON, XML, and CSV
 */

class ApiResponse {
    
    private $requestId;
    private $format;
    
    public function __construct($format = 'json') {
        $this->requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? generateRequestId();
        $this->format = $format;
    }
    
    /**
     * Send success response
     */
    public function success($data, $metadata = [], $statusCode = 200) {
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if (!empty($metadata)) {
            $response['metadata'] = $metadata;
        }
        
        $this->send($response, $statusCode);
    }
    
    /**
     * Send error response
     */
    public function error($errorCode, $message = null, $details = null, $statusCode = 400) {
        $errorMessages = ERROR_MESSAGES;
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message ?? ($errorMessages[$errorCode] ?? 'An error occurred'),
                'request_id' => $this->requestId,
                'timestamp' => date('c')
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        $this->send($response, $statusCode);
    }
    
    /**
     * Send paginated response
     */
    public function paginated($data, $currentPage, $perPage, $totalRecords, $metadata = []) {
        $totalPages = ceil($totalRecords / $perPage);
        
        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$currentPage,
                'per_page' => (int)$perPage,
                'total_pages' => (int)$totalPages,
                'total_records' => (int)$totalRecords
            ]
        ];
        
        // Add links for navigation
        $baseUrl = getApiBaseUrl() . $_SERVER['REQUEST_URI'];
        $baseUrl = strtok($baseUrl, '?');
        
        $response['links'] = [
            'first' => $this->buildPaginationUrl($baseUrl, 1, $perPage),
            'last' => $this->buildPaginationUrl($baseUrl, $totalPages, $perPage),
            'prev' => $currentPage > 1 ? $this->buildPaginationUrl($baseUrl, $currentPage - 1, $perPage) : null,
            'next' => $currentPage < $totalPages ? $this->buildPaginationUrl($baseUrl, $currentPage + 1, $perPage) : null
        ];
        
        if (!empty($metadata)) {
            $response['metadata'] = $metadata;
        }
        
        $this->send($response, 200);
    }
    
    /**
     * Send response with appropriate format
     */
    private function send($data, $statusCode = 200) {
        // Add rate limit headers
        $this->addRateLimitHeaders();
        
        // Add security headers
        $this->addSecurityHeaders();
        
        // Add custom headers
        header('X-Request-ID: ' . $this->requestId);
        header('X-API-Version: ' . API_VERSION);
        
        http_response_code($statusCode);
        
        switch ($this->format) {
            case 'xml':
                $this->sendXml($data);
                break;
            case 'csv':
                $this->sendCsv($data);
                break;
            case 'json':
            default:
                $this->sendJson($data);
                break;
        }
        
        exit;
    }
    
    /**
     * Send JSON response
     */
    private function sendJson($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Send XML response
     */
    private function sendXml($data) {
        header('Content-Type: application/xml; charset=utf-8');
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $this->arrayToXml($data, $xml);
        
        echo $xml->asXML();
    }
    
    /**
     * Convert array to XML recursively
     */
    private function arrayToXml($data, &$xml) {
        foreach ($data as $key => $value) {
            // Handle numeric keys
            if (is_numeric($key)) {
                $key = 'record';
            }
            
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
    
    /**
     * Send CSV response
     */
    private function sendCsv($data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Extract data array
        $records = $data['data'] ?? $data;
        
        if (!empty($records) && is_array($records)) {
            // Write header row
            if (isset($records[0]) && is_array($records[0])) {
                fputcsv($output, array_keys($records[0]));
            }
            
            // Write data rows
            foreach ($records as $record) {
                if (is_array($record)) {
                    fputcsv($output, $record);
                }
            }
        }
        
        fclose($output);
    }
    
    /**
     * Add rate limit headers
     */
    private function addRateLimitHeaders() {
        if (isset($_SERVER['X_RATE_LIMIT_LIMIT'])) {
            header('X-RateLimit-Limit: ' . $_SERVER['X_RATE_LIMIT_LIMIT']);
        }
        if (isset($_SERVER['X_RATE_LIMIT_REMAINING'])) {
            header('X-RateLimit-Remaining: ' . $_SERVER['X_RATE_LIMIT_REMAINING']);
        }
        if (isset($_SERVER['X_RATE_LIMIT_RESET'])) {
            header('X-RateLimit-Reset: ' . $_SERVER['X_RATE_LIMIT_RESET']);
        }
    }
    
    /**
     * Add security headers
     */
    private function addSecurityHeaders() {
        $headers = SECURITY_HEADERS;
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        // CORS headers
        if (ENABLE_CORS) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
            $allowedOrigins = explode(',', CORS_ALLOWED_ORIGINS);
            
            if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
                header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);
                header('Access-Control-Max-Age: 86400');
            }
        }
    }
    
    /**
     * Build pagination URL
     */
    private function buildPaginationUrl($baseUrl, $page, $perPage) {
        $params = $_GET;
        $params['page'] = $page;
        $params['limit'] = $perPage;
        
        return $baseUrl . '?' . http_build_query($params);
    }
    
    /**
     * Determine response format from request
     */
    public static function getRequestedFormat() {
        // Check Accept header
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';
        
        if (strpos($acceptHeader, 'application/xml') !== false) {
            return 'xml';
        } elseif (strpos($acceptHeader, 'text/csv') !== false) {
            return 'csv';
        }
        
        // Check format query parameter
        $format = $_GET['format'] ?? 'json';
        
        return in_array($format, ['json', 'xml', 'csv']) ? $format : 'json';
    }
}

/**
 * Quick response helpers
 */
function apiSuccess($data, $metadata = [], $statusCode = 200) {
    $response = new ApiResponse(ApiResponse::getRequestedFormat());
    $response->success($data, $metadata, $statusCode);
}

function apiError($errorCode, $message = null, $details = null, $statusCode = 400) {
    $response = new ApiResponse('json'); // Always use JSON for errors
    $response->error($errorCode, $message, $details, $statusCode);
}

function apiPaginated($data, $currentPage, $perPage, $totalRecords, $metadata = []) {
    $response = new ApiResponse(ApiResponse::getRequestedFormat());
    $response->paginated($data, $currentPage, $perPage, $totalRecords, $metadata);
}


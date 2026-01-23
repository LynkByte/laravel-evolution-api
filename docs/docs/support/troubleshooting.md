# Troubleshooting Guide

A comprehensive guide to diagnosing and resolving issues with the Laravel Evolution API package.

## Quick Diagnostics

Before diving into specific issues, run these quick checks:

### 1. Package Health Check

```bash
php artisan evolution-api:health-check
```

This command checks:
- API connectivity
- Authentication
- Instance states
- Database connectivity

### 2. Instance Status

```bash
# Check all instances
php artisan evolution-api:instance-status

# Check specific instance
php artisan evolution-api:instance-status my-instance
```

### 3. Verify Configuration

```php
// In tinker or a test route
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

// Check connection
$response = EvolutionApi::instances()->fetchAll();
dd($response->getData());
```

---

## Enable Debug Mode

For detailed error information, enable debug mode:

```env
# .env file
EVOLUTION_DEBUG=true
EVOLUTION_LOG_REQUESTS=true
EVOLUTION_LOG_RESPONSES=true

# Optional: dedicated log channel
EVOLUTION_LOG_CHANNEL=evolution
```

Create a dedicated log channel (optional):

```php
// config/logging.php
'channels' => [
    'evolution' => [
        'driver' => 'daily',
        'path' => storage_path('logs/evolution.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

---

## Common Issues

### Message Timeout Errors

!!! failure "Symptom"
    `MessageTimeoutException`: Message to X timed out after Y seconds

**Step 1: Check Evolution API Logs**

```bash
# Docker
docker logs evolution-api -f --tail 100

# Look for these patterns:
# - "Pre-key upload timeout"
# - "Connection closed"
# - "WebSocket error"
```

**Step 2: Verify Connection State**

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$state = EvolutionApi::for('my-instance')->instances()->connectionState();
dd($state->getData());

// Expected: { "state": "open" }
// Problem states: "close", "connecting", "unknown"
```

**Step 3: Increase Timeout**

```env
# Increase message timeout (default: 60)
EVOLUTION_HTTP_MESSAGE_TIMEOUT=120

# Increase general timeout (default: 30)
EVOLUTION_HTTP_TIMEOUT=60
```

**Step 4: Try Reconnecting**

```php
// Logout and reconnect
EvolutionApi::for('my-instance')->instances()->logout();
sleep(5);
$qr = EvolutionApi::for('my-instance')->instances()->connect();
```

**Step 5: Wait for Connection Stabilization**

```php
$instance = EvolutionApi::for('my-instance')->instances();

// Wait up to 60 seconds for ready state
if ($instance->waitUntilReady(timeout: 60, stabilize: true)) {
    // Now safe to send
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendText('5511999999999', 'Hello!');
}
```

---

### Connection Not Open Errors

!!! failure "Symptom"
    `ConnectionException`: Cannot send message: WhatsApp connection is not open

**Step 1: Check Connection State**

```php
$diagnostics = EvolutionApi::for('my-instance')
    ->instances()
    ->getConnectionDiagnostics();
    
dd($diagnostics);
// Shows: connected, state, ready_to_send, details
```

**Step 2: Reconnect if Disconnected**

```php
if (!$diagnostics['ready_to_send']) {
    // Check if we need to scan QR again
    $connect = EvolutionApi::for('my-instance')->instances()->connect();
    
    if (isset($connect->getData()['qrcode'])) {
        // QR code needs to be scanned
        // Display or send $connect->getData()['qrcode']['base64']
    }
}
```

**Step 3: Disable Connection Verification (Temporary)**

If you need to skip connection checks:

```php
EvolutionApi::for('my-instance')
    ->messages()
    ->withoutConnectionVerification()
    ->sendText('5511999999999', 'Hello!');
```

Or globally via config:

```env
EVOLUTION_VERIFY_CONNECTION=false
```

---

### Authentication Failures

!!! failure "Symptom"
    `AuthenticationException`: Invalid API key or 401 Unauthorized

**Step 1: Verify API Key**

```bash
# Test directly with curl
curl -X GET "https://your-evolution-api.com/instance/fetchInstances" \
  -H "apikey: your-api-key"
```

**Step 2: Check Configuration**

```php
// Verify config is loaded correctly
dd(config('evolution-api.connections.default'));

// Should show:
// [
//     'server_url' => 'https://...',
//     'api_key' => 'your-key',
// ]
```

**Step 3: Clear Config Cache**

```bash
php artisan config:clear
php artisan cache:clear
```

**Step 4: Check .env Variables**

```bash
# Ensure no quotes issues
EVOLUTION_API_KEY=your-key-here  # Correct
EVOLUTION_API_KEY="your-key-here"  # May cause issues with special chars
```

---

### Instance Not Found

!!! failure "Symptom"
    `InstanceNotFoundException`: Instance 'name' not found

**Step 1: List All Instances**

```php
$instances = EvolutionApi::instances()->fetchAll();
dd($instances->getData());
```

**Step 2: Create Instance if Missing**

```php
$response = EvolutionApi::instances()->create([
    'instanceName' => 'my-instance',
    'integration' => 'WHATSAPP-BAILEYS',
    'qrcode' => true,
]);
```

**Step 3: Check Instance Name Spelling**

Instance names are case-sensitive:

```php
// These are different instances:
EvolutionApi::for('MyInstance');
EvolutionApi::for('myinstance');
EvolutionApi::for('my-instance');
```

---

### Rate Limit Exceeded

!!! failure "Symptom"
    `RateLimitException`: Rate limit exceeded, retry after X seconds

**Step 1: Check Current Limits**

```php
catch (RateLimitException $e) {
    $retryAfter = $e->getRetryAfter();
    $limitType = $e->getLimitType();
    
    Log::warning("Rate limited", [
        'retry_after' => $retryAfter,
        'type' => $limitType,
    ]);
}
```

**Step 2: Implement Backoff**

```php
use Lynkbyte\EvolutionApi\Jobs\SendMessageJob;

// In your exception handler
catch (RateLimitException $e) {
    // Re-queue with delay
    SendMessageJob::dispatch($instance, $type, $dto)
        ->delay(now()->addSeconds($e->getRetryAfter()));
}
```

**Step 3: Reduce Send Rate**

```php
// Add delays between messages
foreach ($recipients as $recipient) {
    EvolutionApi::for('instance')
        ->messages()
        ->sendText($recipient, $message);
    
    usleep(100000); // 100ms delay
}
```

**Step 4: Use Queue with Rate Limiting**

```php
// config/evolution-api.php
'queue' => [
    'enabled' => true,
    'rate_limit' => [
        'messages_per_second' => 10,
    ],
],
```

---

### Messages Sent But Not Delivered

!!! failure "Symptom"
    No errors, API returns success, but recipient never receives message

**Step 1: Verify Number Format**

```php
// Correct: country code + number, no formatting
$number = '5511999999999';

// Wrong formats:
$number = '+5511999999999';  // No + prefix
$number = '55 11 99999-9999';  // No spaces/dashes
$number = '011999999999';  // No leading zeros
```

**Step 2: Check if Number is on WhatsApp**

```php
$check = EvolutionApi::for('instance')
    ->chats()
    ->checkNumber('5511999999999');
    
if ($check->getData()['exists'] ?? false) {
    // Number is registered on WhatsApp
}
```

**Step 3: Check Message Status via Webhook**

Set up webhooks to track delivery:

```php
// In your webhook handler
public function handle(WebhookPayloadDto $payload): void
{
    if ($payload->event === 'MESSAGES_UPDATE') {
        $status = $payload->data['status'] ?? null;
        // 'PENDING', 'SENT', 'DELIVERED', 'READ', 'FAILED'
        
        Log::info("Message status update", [
            'message_id' => $payload->data['key']['id'] ?? null,
            'status' => $status,
        ]);
    }
}
```

**Step 4: Check for Blocks**

The recipient may have blocked your number. There's no API to detect this, but signs include:
- Messages show as sent but never delivered
- Single checkmark only (never double)
- Works for other numbers

---

### QR Code Scanning Issues

!!! failure "Symptom"
    QR code scans but connection doesn't establish, or QR keeps refreshing

**Step 1: Check QR Code Event**

```php
$connect = EvolutionApi::for('instance')->instances()->connect();

$data = $connect->getData();
if (isset($data['qrcode'])) {
    // Display the QR
    $base64 = $data['qrcode']['base64'];
    // Or use $data['qrcode']['code'] for text representation
}
```

**Step 2: Monitor Connection via Webhook**

```php
// Listen for CONNECTION_UPDATE events
if ($payload->event === 'CONNECTION_UPDATE') {
    $state = $payload->data['state'] ?? 'unknown';
    Log::info("Connection state: {$state}");
}
```

**Step 3: Check Phone Requirements**

- Phone must have active internet connection
- WhatsApp must be updated to latest version
- Phone must stay connected for initial sync

**Step 4: Try Fresh Instance**

```php
// Delete and recreate
EvolutionApi::for('old-instance')->instances()->delete();

EvolutionApi::instances()->create([
    'instanceName' => 'new-instance',
    'integration' => 'WHATSAPP-BAILEYS',
]);
```

---

## Reading Evolution API Logs

### Docker Logs

```bash
# Follow logs in real-time
docker logs evolution-api -f

# Last 500 lines
docker logs evolution-api --tail 500

# With timestamps
docker logs evolution-api -t --tail 100
```

### Common Log Patterns

| Pattern | Meaning | Action |
|---------|---------|--------|
| `Pre-key upload timeout` | Encryption handshake failed | Reconnect instance |
| `Connection closed` | WebSocket dropped | Will auto-reconnect |
| `Rate limited` | Too many requests | Slow down |
| `Session not found` | Session data missing | Re-scan QR code |
| `Invalid API key` | Auth failure | Check API key |

### Log Level Configuration

In Evolution API's environment:

```env
# Set in Evolution API's .env or docker-compose
LOG_LEVEL=debug  # debug, info, warn, error
```

---

## Network Debugging

### Test Connectivity

```bash
# From Laravel server to Evolution API
curl -v https://your-evolution-api.com/health

# Check SSL
openssl s_client -connect your-evolution-api.com:443

# DNS resolution
nslookup your-evolution-api.com
```

### Firewall Rules

Ensure these ports are open:

| Service | Port | Direction |
|---------|------|-----------|
| Evolution API | 8080 (default) | Inbound to Evolution API |
| HTTPS | 443 | Outbound from Evolution API |
| WSS | 443 | Outbound from Evolution API |

### Docker Network Issues

```bash
# Check container network
docker network inspect bridge

# Test from inside container
docker exec -it evolution-api curl https://web.whatsapp.com
```

---

## Database Troubleshooting

### Migration Issues

```bash
# Check migration status
php artisan migrate:status | grep evolution

# Re-run migrations
php artisan migrate --path=vendor/lynkbyte/laravel-evolution-api/database/migrations

# Rollback and re-migrate
php artisan migrate:rollback --step=1
php artisan migrate
```

### Webhook Log Table Full

```bash
# Prune old data
php artisan evolution-api:prune --days=7

# Check table size
SELECT 
    table_name,
    round(data_length/1024/1024, 2) as 'Size (MB)'
FROM information_schema.tables 
WHERE table_name LIKE 'evolution_%';
```

---

## Performance Issues

### Slow Message Sending

1. **Check connection pool settings:**

```env
EVOLUTION_HTTP_POOL_SIZE=10
EVOLUTION_HTTP_KEEP_ALIVE=true
```

2. **Use async sending:**

```php
// Queue messages instead of sending directly
SendMessageJob::dispatch('instance', 'text', $dto);
```

3. **Batch operations:**

```php
// Bad: many small requests
foreach ($messages as $msg) {
    EvolutionApi::messages()->sendText(...);
}

// Better: queue all
foreach ($messages as $msg) {
    SendMessageJob::dispatch(...);
}
```

### High Memory Usage

1. **Limit webhook log retention:**

```php
// config/evolution-api.php
'database' => [
    'log_webhooks' => true,
    'webhook_retention_days' => 7,  // Reduce from default
],
```

2. **Use chunk processing:**

```php
EvolutionMessage::where('created_at', '<', now()->subDays(30))
    ->chunkById(1000, function ($messages) {
        $messages->each->delete();
    });
```

---

## Getting Help

If you've tried the above and still have issues:

1. **Gather information:**
   - Laravel version
   - Package version
   - Evolution API version
   - Error messages (full stack trace)
   - Relevant logs

2. **Check existing issues:**
   - [Package issues](https://github.com/lynkbyte/laravel-evolution-api/issues)
   - [Evolution API issues](https://github.com/EvolutionAPI/evolution-api/issues)
   - [Baileys issues](https://github.com/WhiskeySockets/Baileys/issues)

3. **Open a new issue** with:
   - Clear description of the problem
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details

---

## Related Pages

- [Known Limitations](known-limitations.md) - Understand inherent limitations
- [FAQ](faq.md) - Frequently asked questions
- [Error Handling](../advanced/error-handling.md) - Exception handling guide

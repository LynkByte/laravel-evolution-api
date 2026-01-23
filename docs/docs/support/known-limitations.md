# Known Limitations

This page documents known limitations of the Laravel Evolution API package and the underlying Evolution API/Baileys infrastructure.

## Pre-Key Upload Timeout Issue

!!! warning "Upstream Issue"
    This is a **known issue in the Baileys WhatsApp library**, not a bug in this Laravel package.

### What Happens

Evolution API uses the [Baileys library](https://github.com/WhiskeySockets/Baileys) to communicate with WhatsApp. Before sending messages, Baileys must complete an encryption handshake by uploading "pre-keys" to WhatsApp servers.

Sometimes this handshake fails or times out, causing:

- Connection shows as "open" (QR code scanned successfully)
- Receiving messages works fine
- Sending messages fails with timeout errors
- Evolution API logs show "Pre-key upload timeout" errors

### Root Causes

| Cause | Description |
|-------|-------------|
| Network latency | High latency between Evolution API server and WhatsApp servers |
| WhatsApp rate limiting | WhatsApp temporarily throttling the connection |
| Server overload | Evolution API server under heavy load |
| Baileys bugs | Occasional bugs in the Baileys library's key management |
| Docker networking | Network issues in containerized deployments |

### What This Package Does to Help

1. **Longer message timeouts** - Message operations use 60s timeout vs 30s for other operations
2. **Connection verification** - Optionally verify connection state before sending
3. **Helpful exceptions** - `MessageTimeoutException` includes diagnostic info and suggestions
4. **Pre-key detection** - `isPossiblePreKeyIssue()` method to identify this specific problem

### Handling Pre-Key Issues

```php
use Lynkbyte\EvolutionApi\Exceptions\MessageTimeoutException;
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

try {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->sendText('5511999999999', 'Hello!');
} catch (MessageTimeoutException $e) {
    if ($e->isPossiblePreKeyIssue()) {
        // Log the issue
        Log::warning('Possible pre-key issue detected', [
            'instance' => $e->getInstanceName(),
            'suggestions' => $e->getSuggestions(),
        ]);
        
        // Try reconnecting
        EvolutionApi::for('my-instance')->instances()->logout();
        sleep(5);
        EvolutionApi::for('my-instance')->instances()->connect();
        
        // Queue for retry
        SendMessageJob::dispatch(...)->delay(now()->addMinutes(2));
    }
}
```

### Workarounds

1. **Wait and retry** - The issue is often temporary (seconds to minutes)
2. **Reconnect the instance** - Logout and reconnect to force new key exchange
3. **Use `waitUntilReady()`** - Wait for connection to stabilize after connecting
4. **Increase timeouts** - Set `EVOLUTION_HTTP_MESSAGE_TIMEOUT=120`
5. **Check Evolution API version** - Some versions handle this better than others

---

## Evolution API Version Compatibility

Not all Evolution API versions work equally well. Here's our compatibility matrix:

| Evolution API Version | Status | Notes |
|----------------------|--------|-------|
| v2.3.7+ | :material-check-circle:{ .green } Recommended | Best stability, build from source recommended |
| v2.3.0 - v2.3.6 | :material-check-circle:{ .green } Works well | Good compatibility |
| v2.2.x | :material-alert:{ .yellow } Works | May have stability issues |
| v2.1.x | :material-alert:{ .yellow } Partial | Connection issues with Docker Hub images |
| v2.0.x | :material-close-circle:{ .red } Untested | May not work with this package |
| v1.x | :material-close-circle:{ .red } Not supported | Different API structure |

### Recommendation

For production use, we recommend:

1. **Build Evolution API from source** using the latest Baileys version
2. Use **v2.3.7 or later**
3. **Avoid Docker Hub pre-built images** for v2.1.x (known issues)

```bash
# Build from source (recommended)
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
git checkout v2.3.7  # or latest stable tag
docker-compose up -d
```

---

## WhatsApp Platform Limitations

These are limitations imposed by WhatsApp itself, not by this package or Evolution API.

### Message Limits

| Limit | Value | Notes |
|-------|-------|-------|
| Messages per second | ~60-80 | Varies by account age and reputation |
| Messages per day (new number) | ~250 | Increases over time with good reputation |
| Messages per day (established) | ~1,000+ | High-trust accounts can send more |
| Broadcast list size | 256 | Maximum recipients per broadcast |
| Group size | 1,024 | Maximum participants per group |

!!! info "Business API Limits"
    WhatsApp Business API (cloud) has different, often higher limits. This package primarily works with the unofficial Baileys-based API.

### Media Limits

| Media Type | Max Size | Supported Formats |
|------------|----------|-------------------|
| Images | 5 MB | JPEG, PNG |
| Videos | 16 MB | MP4, 3GPP |
| Audio | 16 MB | AAC, MP3, OGG, AMR |
| Documents | 100 MB | PDF, DOC, XLS, PPT, etc. |
| Stickers | 500 KB | WebP (static), WebP (animated) |

### Rate Limiting Behavior

WhatsApp implements invisible rate limiting:

- **Soft limits**: Messages queue on WhatsApp's servers, delivery slows
- **Hard limits**: Account temporarily restricted or banned
- **Quality-based**: Low engagement = stricter limits

### Number Format Requirements

```php
// Correct formats
'5511999999999'      // Country code + area code + number
'551199999999'       // Works too (WhatsApp normalizes)

// Incorrect formats
'+5511999999999'     // No + prefix
'55 11 99999-9999'   // No spaces or dashes
'011999999999'       // No leading zeros for country code
```

---

## Baileys Library Limitations

The Baileys library has some inherent limitations:

### Session Management

- Sessions are stored locally and can become corrupted
- Session files must be backed up for disaster recovery
- Multiple instances sharing sessions can cause conflicts

### Connection Stability

- WebSocket connections may drop unexpectedly
- Reconnection isn't always automatic
- Connection state can be inconsistent

### Feature Support

| Feature | Support | Notes |
|---------|---------|-------|
| Text messages | :material-check-circle: Full | All features work |
| Media messages | :material-check-circle: Full | All types supported |
| Groups | :material-check-circle: Full | Create, manage, message |
| Status/Stories | :material-check-circle: Full | View and post |
| Calls | :material-close-circle: None | Cannot make/receive calls |
| End-to-end encryption | :material-check-circle: Full | Handled by Baileys |
| Multi-device | :material-check-circle: Full | Supported |
| Message reactions | :material-check-circle: Full | Send and receive |
| Message editing | :material-alert: Partial | Receiving only in some versions |

---

## Docker Deployment Considerations

### Memory Requirements

Evolution API with Baileys requires significant memory:

| Instances | Recommended RAM | Minimum RAM |
|-----------|-----------------|-------------|
| 1-5 | 2 GB | 1 GB |
| 5-20 | 4 GB | 2 GB |
| 20-50 | 8 GB | 4 GB |
| 50+ | 16 GB+ | 8 GB |

### Storage Requirements

Each instance requires storage for:

- Session data (~5-50 MB per instance)
- Media cache (varies by usage)
- Logs (configure rotation)

### Network Configuration

```yaml
# docker-compose.yml recommendations
services:
  evolution-api:
    # Use host networking for better WebSocket performance
    network_mode: host
    
    # Or configure proper port mapping
    ports:
      - "8080:8080"
    
    # Health check
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3
```

### Persistence

!!! danger "Data Loss Warning"
    Always mount volumes for session persistence. Losing sessions means users must re-scan QR codes.

```yaml
volumes:
  - ./evolution_store:/evolution/store
  - ./evolution_instances:/evolution/instances
```

---

## Package-Specific Limitations

### Concurrent Requests

The package doesn't implement request queuing at the package level. For high-volume applications:

1. Use Laravel's queue system for message sending
2. Implement your own rate limiting
3. Consider multiple Evolution API servers

### Webhook Processing

- Webhooks are processed synchronously by default
- For high-volume webhooks, use the `ProcessWebhookJob` for async processing
- Webhook signature verification adds slight latency

### Testing

- The `EvolutionApiFake` doesn't simulate all real-world behaviors
- Integration tests should use a real Evolution API instance
- Webhook testing requires manual trigger or mocking

---

## Getting Updates

Evolution API and Baileys are actively developed. To stay informed:

1. Watch the [Evolution API releases](https://github.com/EvolutionAPI/evolution-api/releases)
2. Monitor [Baileys issues](https://github.com/WhiskeySockets/Baileys/issues) for known problems
3. Check this package's changelog for compatibility updates

---

## Related Pages

- [Troubleshooting Guide](troubleshooting.md) - Step-by-step problem resolution
- [FAQ](faq.md) - Frequently asked questions
- [Error Handling](../advanced/error-handling.md) - Exception handling best practices

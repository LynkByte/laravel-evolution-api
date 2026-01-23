---
title: Audio Messages
description: Sending audio and voice messages with Laravel Evolution API
---

# Audio Messages

Send audio files and voice messages through WhatsApp.

## Supported Formats

| Format | Extension | Notes |
|--------|-----------|-------|
| MP3 | .mp3 | Most common |
| OGG | .ogg | Opus codec preferred |
| WAV | .wav | Larger file size |
| AAC | .aac | Good compression |
| M4A | .m4a | Apple format |

## Sending Audio

### Simple Helper

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->audio('5511999999999', 'https://example.com/audio.mp3');
```

### With Delay

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->audio(
        number: '5511999999999',
        audio: 'https://example.com/audio.mp3',
        delay: 1000
    );
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendAudioMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendAudio([
        'number' => '5511999999999',
        'audio' => 'https://example.com/voice.ogg',
        'delay' => 1000,
    ]);

// Or with constructor
$dto = new SendAudioMessageDto(
    number: '5511999999999',
    audio: 'https://example.com/voice.ogg',
    delay: 1000
);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendAudio($dto);
```

## Voice Messages (PTT)

Audio sent via `sendAudio` appears as a voice message (push-to-talk) in WhatsApp, showing the waveform player.

```php
// This appears as a voice message
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->audio('5511999999999', 'https://example.com/voice.ogg');
```

## From Base64

```php
$audioData = base64_encode(file_get_contents('voice.mp3'));

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendAudio([
        'number' => '5511999999999',
        'audio' => "data:audio/mpeg;base64,{$audioData}",
    ]);
```

## From Laravel Storage

```php
use Illuminate\Support\Facades\Storage;

$path = Storage::path('audio/message.mp3');
$audioData = base64_encode(file_get_contents($path));
$mimeType = mime_content_type($path);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendAudio([
        'number' => '5511999999999',
        'audio' => "data:{$mimeType};base64,{$audioData}",
    ]);
```

## Audio vs Media (Document)

| Method | Appears As | Use Case |
|--------|------------|----------|
| `sendAudio()` | Voice message with waveform | Voice notes, audio messages |
| `sendMedia()` with `audio` type | Audio file attachment | Music, podcasts, audio files |

### Send as Audio File (Not Voice)

To send audio as a downloadable file instead of voice message:

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'document',
        'media' => 'https://example.com/podcast.mp3',
        'fileName' => 'podcast-episode-1.mp3',
        'mimetype' => 'audio/mpeg',
    ]);
```

## Recording Indicator

Show recording indicator before sending:

```php
// Show "recording..." indicator
EvolutionApi::for('my-instance')
    ->messages()
    ->recording('5511999999999');

// Wait a moment
sleep(2);

// Then send audio
EvolutionApi::for('my-instance')
    ->messages()
    ->audio('5511999999999', 'https://example.com/voice.ogg');
```

## MIME Types

| Format | MIME Type |
|--------|-----------|
| MP3 | audio/mpeg |
| OGG | audio/ogg |
| WAV | audio/wav |
| AAC | audio/aac |
| M4A | audio/mp4 |

## Best Practices

### Optimal Format

OGG with Opus codec provides the best quality-to-size ratio for voice messages:

```php
// OGG is preferred for voice messages
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->audio('5511999999999', 'https://example.com/voice.ogg');
```

### File Size

- Keep voice messages under 16MB
- Shorter is better for voice messages
- For longer audio, consider sending as document

### Quality

- 16kHz sample rate is sufficient for voice
- Higher bitrates improve music quality but increase size

## Examples

### Send Automated Voice Response

```php
public function sendVoiceResponse(string $phone, string $responseType): void
{
    $audioFiles = [
        'welcome' => 'https://example.com/audio/welcome.ogg',
        'menu' => 'https://example.com/audio/menu.ogg',
        'goodbye' => 'https://example.com/audio/goodbye.ogg',
    ];
    
    if (isset($audioFiles[$responseType])) {
        EvolutionApi::for('my-instance')
            ->messages()
            ->audio($phone, $audioFiles[$responseType]);
    }
}
```

### Send Text-to-Speech

```php
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;

public function sendTTS(string $phone, string $text): void
{
    // Generate audio using Google TTS (example)
    $client = new TextToSpeechClient();
    $response = $client->synthesizeSpeech(/* ... */);
    $audioContent = $response->getAudioContent();
    
    $audioBase64 = base64_encode($audioContent);
    
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendAudio([
            'number' => $phone,
            'audio' => "data:audio/mp3;base64,{$audioBase64}",
        ]);
}
```

### Send with Recording Simulation

```php
public function sendVoiceWithSimulation(string $phone, string $audioUrl): void
{
    // Show recording indicator
    EvolutionApi::for('my-instance')
        ->messages()
        ->recording($phone);
    
    // Simulate recording time
    sleep(3);
    
    // Stop indicator and send
    EvolutionApi::for('my-instance')
        ->messages()
        ->stopPresence($phone);
    
    EvolutionApi::for('my-instance')
        ->messages()
        ->audio($phone, $audioUrl);
}
```

---

## Next Steps

- [Media Messages](media-messages.md) - Images, videos, documents
- [Location & Contacts](location-contact.md) - Send locations and contacts
- [Interactive Messages](interactive.md) - Buttons and lists

---
title: Media Messages
description: Sending images, videos, and documents with Laravel Evolution API
---

# Media Messages

Send images, videos, and documents through WhatsApp using Laravel Evolution API.

## Supported Media Types

| Type | Extensions | Max Size |
|------|------------|----------|
| Image | jpg, jpeg, png, gif, webp | 16 MB |
| Video | mp4, 3gp, mov | 16 MB |
| Document | pdf, doc, docx, xls, xlsx, ppt, pptx, txt | 100 MB |

## Sending Images

### Simple Helper

```php
use Lynkbyte\EvolutionApi\Facades\EvolutionApi;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->image(
        number: '5511999999999',
        media: 'https://example.com/image.jpg',
        caption: 'Check out this image!'
    );
```

### Using DTO

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => 'https://example.com/image.jpg',
        'caption' => 'Image caption',
        'fileName' => 'photo.jpg',
    ]);
```

### From Base64

```php
$imageData = base64_encode(file_get_contents('photo.jpg'));

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => "data:image/jpeg;base64,{$imageData}",
        'caption' => 'Uploaded photo',
    ]);
```

### From Laravel Storage

```php
use Illuminate\Support\Facades\Storage;

$path = Storage::path('images/photo.jpg');
$imageData = base64_encode(file_get_contents($path));
$mimeType = mime_content_type($path);

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'image',
        'media' => "data:{$mimeType};base64,{$imageData}",
    ]);
```

## Sending Videos

### Simple Helper

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->video(
        number: '5511999999999',
        media: 'https://example.com/video.mp4',
        caption: 'Watch this video!'
    );
```

### Using DTO

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'video',
        'media' => 'https://example.com/video.mp4',
        'caption' => 'Video caption',
        'fileName' => 'video.mp4',
    ]);
```

## Sending Documents

### Simple Helper

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->document(
        number: '5511999999999',
        media: 'https://example.com/document.pdf',
        caption: 'Here is the document',
        fileName: 'report.pdf',
        mimetype: 'application/pdf'
    );
```

### Using DTO

```php
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'document',
        'media' => 'https://example.com/spreadsheet.xlsx',
        'caption' => 'Monthly report',
        'fileName' => 'report-2024.xlsx',
        'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
```

### From Laravel Storage (Document)

```php
use Illuminate\Support\Facades\Storage;

$path = Storage::path('documents/invoice.pdf');
$pdfData = base64_encode(file_get_contents($path));

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->sendMedia([
        'number' => '5511999999999',
        'mediatype' => 'document',
        'media' => "data:application/pdf;base64,{$pdfData}",
        'fileName' => 'invoice.pdf',
        'caption' => 'Your invoice is attached',
    ]);
```

## SendMediaMessageDto Reference

```php
use Lynkbyte\EvolutionApi\DTOs\Message\SendMediaMessageDto;

$dto = new SendMediaMessageDto(
    number: '5511999999999',           // Required: recipient
    mediatype: 'image',                // Required: image, video, document
    media: 'https://...',              // Required: URL or base64
    caption: 'Optional caption',       // Optional
    fileName: 'file.jpg',              // Optional: display name
    mimetype: 'image/jpeg',            // Optional: MIME type
    delay: 1000,                       // Optional: delay in ms
);
```

## Media from URLs

When using URLs:

- URL must be publicly accessible
- HTTPS is recommended
- URL should not require authentication
- Direct file URLs work best (avoid redirects)

```php
// Good examples
$response = EvolutionApi::for('my-instance')
    ->messages()
    ->image('5511999999999', 'https://example.com/images/photo.jpg');

$response = EvolutionApi::for('my-instance')
    ->messages()
    ->document('5511999999999', 'https://example.com/files/report.pdf');
```

## Media from Base64

For local files or generated content:

```php
// Image from file
$imageBase64 = base64_encode(file_get_contents('/path/to/image.jpg'));
$media = "data:image/jpeg;base64,{$imageBase64}";

// PDF from file
$pdfBase64 = base64_encode(file_get_contents('/path/to/document.pdf'));
$media = "data:application/pdf;base64,{$pdfBase64}";

// Generated image (e.g., QR code)
$qrCode = QrCode::format('png')->size(300)->generate('https://example.com');
$media = "data:image/png;base64," . base64_encode($qrCode);
```

## MIME Types Reference

### Images
| Extension | MIME Type |
|-----------|-----------|
| jpg, jpeg | image/jpeg |
| png | image/png |
| gif | image/gif |
| webp | image/webp |

### Videos
| Extension | MIME Type |
|-----------|-----------|
| mp4 | video/mp4 |
| 3gp | video/3gpp |
| mov | video/quicktime |

### Documents
| Extension | MIME Type |
|-----------|-----------|
| pdf | application/pdf |
| doc | application/msword |
| docx | application/vnd.openxmlformats-officedocument.wordprocessingml.document |
| xls | application/vnd.ms-excel |
| xlsx | application/vnd.openxmlformats-officedocument.spreadsheetml.sheet |
| ppt | application/vnd.ms-powerpoint |
| pptx | application/vnd.openxmlformats-officedocument.presentationml.presentation |
| txt | text/plain |

## Error Handling

```php
use Lynkbyte\EvolutionApi\Exceptions\ValidationException;

try {
    $response = EvolutionApi::for('my-instance')
        ->messages()
        ->image('5511999999999', 'https://example.com/image.jpg');
        
    if (!$response->isSuccessful()) {
        // Handle API error
        logger()->error('Media send failed', [
            'status' => $response->status(),
            'error' => $response->json('message'),
        ]);
    }
} catch (ValidationException $e) {
    // Invalid parameters
    logger()->error('Validation error', ['errors' => $e->getErrors()]);
}
```

## Examples

### Send Invoice PDF

```php
public function sendInvoice(Order $order): void
{
    // Generate PDF
    $pdf = PDF::loadView('invoices.template', ['order' => $order]);
    $pdfBase64 = base64_encode($pdf->output());
    
    EvolutionApi::for('my-instance')
        ->messages()
        ->sendMedia([
            'number' => $order->customer_phone,
            'mediatype' => 'document',
            'media' => "data:application/pdf;base64,{$pdfBase64}",
            'fileName' => "invoice-{$order->id}.pdf",
            'caption' => "Invoice for Order #{$order->id}",
        ]);
}
```

### Send Product Image

```php
public function sendProductInfo(Product $product, string $phone): void
{
    // Send product image
    EvolutionApi::for('my-instance')
        ->messages()
        ->image(
            number: $phone,
            media: $product->image_url,
            caption: "*{$product->name}*\n\n" .
                    "{$product->description}\n\n" .
                    "Price: \${$product->price}"
        );
}
```

### Send Multiple Images

```php
public function sendGallery(array $imageUrls, string $phone): void
{
    foreach ($imageUrls as $index => $url) {
        EvolutionApi::for('my-instance')
            ->messages()
            ->image($phone, $url, "Image " . ($index + 1));
        
        // Small delay between sends
        usleep(500000);
    }
}
```

---

## Next Steps

- [Audio Messages](audio-messages.md) - Send voice messages
- [Location & Contacts](location-contact.md) - Send locations and contact cards
- [Interactive Messages](interactive.md) - Buttons and lists

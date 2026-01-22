# Certificate Template Guide

## Sample A4 Landscape Certificate Template

This directory contains certificate templates for generating professional certificates.

### Template Specification

**Format:** A4 Landscape (297mm x 210mm)

**Sample Template Structure:**

```html
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        /* Template styles */
    </style>
</head>
<body>
    <div class="certificate">
        <!-- Certificate content with placeholders -->
    </div>
</body>
</html>
```

### Template Variables

Use these placeholders in your template:

- `{{RECIPIENT_NAME}}` - Name of the certificate recipient
- `{{INSTITUTION}}` - Recipient's institution/organization
- `{{EVENT_NAME}}` - Name of the event/course/program
- `{{EVENT_DATE}}` - Date of the event
- `{{ORGANIZER}}` - Event organizer name
- `{{CERTIFICATE_NUMBER}}` - Unique certificate number
- `{{QR_CODE}}` - QR code image for verification

### Template Elements

A professional certificate template should include:

1. **Header Section**
   - Logo or icon (60-80mm diameter recommended)
   - Certificate title ("CERTIFICATE" or "CERTIFICATE OF ACHIEVEMENT")
   - Subtitle (optional: "of Achievement", "of Participation", etc.)

2. **Body Section**
   - Introduction text ("This is to certify that...")
   - Recipient name (large, prominent, with underline)
   - Institution name (if applicable)
   - Event details section with:
     - Event name (bold, prominent)
     - Event date
     - Organizer information

3. **Footer Section**
   - Signature blocks (typically 2):
     - Director/Authority signature
     - Organizer signature
   - QR code for verification (25mm x 25mm recommended)
   - Certificate number display

4. **Decorative Elements**
   - Border design (optional)
   - Corner decorations
   - Background gradients or patterns
   - Color accents matching your brand

### Layout Guidelines

**Margins:**
- Outer margin: 15mm
- Inner content padding: 20mm

**Typography:**
- Title: 48px, bold, uppercase
- Recipient name: 42px, bold
- Event name: 24px, semibold
- Body text: 14-16px
- Footer text: 10-12px

**Colors:**
- Primary: #667eea (purple-blue)
- Secondary: #764ba2 (purple)
- Accent: Use gradients for visual appeal
- Text: Dark colors (#333, #666) on white background

### Creating Your Template

1. Create an HTML file in this directory
2. Use A4 landscape dimensions (297mm x 210mm)
3. Include the placeholder variables where content should appear
4. Test the template by generating a sample certificate
5. Adjust layout and styling as needed

### Example Template Features

The sample template (`sample-a4-landscape.html`) includes:
- Gradient border design
- Decorative corner elements
- Circular logo area with gradient background
- Professional typography hierarchy
- Dual signature blocks
- QR code placement area
- Certificate number footer
- Responsive to content changes

### Integration

Templates are used by the certificate generation system to create PDFs. The system:
1. Loads the template HTML
2. Replaces placeholder variables with actual data
3. Converts to PDF format
4. Saves to the `generated/certificates/` directory

### Tips

- Keep designs professional and clean
- Use web-safe fonts or embed custom fonts
- Test print output before finalizing
- Ensure QR code area is visible and scannable
- Maintain proper spacing for signatures
- Use high-resolution images if including logos

---

For more information, see the main documentation in `/README.md`

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management System - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
        }
        .hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .feature-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.15);
        }
        .feature-card i {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .btn-hero {
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 30px;
            margin: 10px;
        }
        .btn-verify {
            background: white;
            color: #667eea;
        }
        .btn-verify:hover {
            background: #f8f9fa;
            color: #5568d3;
        }
        .btn-admin {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        .btn-admin:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>🎓 Certificate Management System</h1>
            <p>Professional Certificate Generation & Verification Platform</p>
            
            <div class="mt-5">
                <a href="verify/index.php" class="btn btn-verify btn-hero">
                    <i class="bi bi-search"></i> Verify Certificate
                </a>
                <a href="admin/login.php" class="btn btn-admin btn-hero">
                    <i class="bi bi-lock"></i> Admin Login
                </a>
            </div>
        </div>
        
        <div class="row justify-content-center mt-5">
            <div class="col-md-3">
                <div class="feature-card">
                    <i class="bi bi-file-earmark-check"></i>
                    <h4>Generate Certificates</h4>
                    <p>Create professional certificates with custom templates</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card">
                    <i class="bi bi-qr-code"></i>
                    <h4>QR Code Verification</h4>
                    <p>Secure verification with QR codes and HMAC signatures</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card">
                    <i class="bi bi-upload"></i>
                    <h4>Batch Processing</h4>
                    <p>Import CSV/Excel and generate thousands of certificates</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card">
                    <i class="bi bi-shield-check"></i>
                    <h4>Secure & Audited</h4>
                    <p>Complete audit trail with role-based access control</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <p class="text-white-50">
                Built with PHP Native & MySQL | Secure | Fast | Reliable
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

-- Sample Data for Certificate Management System
USE sertifikat_db;

-- Insert default admin user
-- Password: admin123 (change this in production!)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('designer', 'designer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'designer'),
('verifier', 'verifier@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'verifikator');

-- Insert sample template
INSERT INTO templates (template_name, template_file, layout_config) VALUES
('Default Certificate Template', 'default_template.jpg', '{"name_x": 100, "name_y": 120, "qr_x": 250, "qr_y": 160}');

-- Insert sample event
INSERT INTO events (event_name, event_date, organizer, template_id, created_by) VALUES
('Workshop Web Development 2026', '2026-01-15', 'Tech Academy Indonesia', 1, 1),
('Seminar Cyber Security', '2026-02-20', 'IT Security Institute', 1, 1);

-- Note: Sample certificates will be generated through the application
-- This is just the initial seed data for testing

-- Sample data note
SELECT 'Database seeded successfully!' AS message;
SELECT 'Default credentials:' AS info;
SELECT 'Username: admin, Password: admin123' AS admin_login;
SELECT 'Username: designer, Password: admin123' AS designer_login;
SELECT 'Username: verifier, Password: admin123' AS verifier_login;
SELECT '⚠️  CHANGE ALL PASSWORDS IN PRODUCTION!' AS warning;

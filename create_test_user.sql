INSERT INTO user (uuid, email, password, username, roles, is_active, created_at, first_name, last_name) 
VALUES (
    UUID(), 
    'test@musehub.com', 
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'testuser', 
    '["ROLE_USER"]', 
    1, 
    NOW(),
    'Test',
    'User'
) ON DUPLICATE KEY UPDATE email=email;

SELECT id, email, username FROM user WHERE email = 'test@musehub.com';

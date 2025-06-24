<?php
$plaintext_password = "aj123";


$hashed_password = password_hash($plaintext_password, PASSWORD_DEFAULT);

echo "<!DOCTYPE html>
      <html lang='en'>
      <head>
          <meta charset='UTF-8'>
          <title>Password Hash Generator</title>
          <style>
              body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; padding: 2rem; background-color: #f4f4f9; color: #333; }
              .container { max-width: 800px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
              h1 { color: #b91c1c; }
              p { font-size: 1.1rem; line-height: 1.6; }
              code { background-color: #eee; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 1rem; }
          </style>
      </head>
      <body>
          <div class='container'>
              <h1>Password Hash Generator</h1>
              <p>Plaintext password: <strong>" . htmlspecialchars($plaintext_password) . "</strong></p>
              <p>Generated BCRYPT Hash:</p>
              <code>" . htmlspecialchars($hashed_password) . "</code>
              <hr style='margin: 1rem 0;'>
              <p><strong>Next Step:</strong> Copy the full hash string above and update the user's 'Password' field in your database.</p>
          </div>
      </body>
      </html>";

?>

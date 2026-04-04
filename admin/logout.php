<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script>
        async function logout() {
            // Using JS to logout of Supabase before redirecting
            const SUPABASE_URL = "<?php echo SUPABASE_URL; ?>";
            const SUPABASE_KEY = "<?php echo SUPABASE_ANON_KEY; ?>";
            const sbClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
            await sbClient.auth.signOut();
            window.location.href = "finish_logout.php";
        }
        logout();
    </script>
</head>
<body>Logging out...</body>
</html>
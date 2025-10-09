<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 shadow" style="width: 400px;">
  <h3 class="text-center mb-3">Forgot Password</h3>
  <form action="send_reset.php" method="POST">
    <div class="mb-3">
      <label for="email" class="form-label">Enter your registered Gmail</label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
  </form>
</div>

</body>
</html>

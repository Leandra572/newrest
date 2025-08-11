<?php
require 'includes/auth.php';
require 'includes/db.php';

// Buscar raspadinhas ativas do banco de dados
$raspadinhas = array();
try {
    $stmt = $conn->query("SELECT * FROM raspadinhas WHERE ativo = 1 ORDER BY preco ASC");
    if ($stmt) {
        $raspadinhas = $stmt->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $raspadinhas = array();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Escolha sua Raspadinha</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center p-6">

  <h1 class="text-3xl font-bold text-green-700 mb-2">⭐ Escolha sua Raspadinha</h1>
  <p class="text-gray-600 mb-8">Escolha sua raspadinha e tente a sorte!</p>

  <?php if (empty($raspadinhas)): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
      <p class="font-bold">Nenhuma raspadinha disponível no momento!</p>
      <p>Entre em contato com o administrador para mais informações.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full max-w-6xl">
      <?php foreach ($raspadinhas as $raspadinha): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 text-center relative hover:shadow-xl transition-shadow duration-300">
          <div class="absolute top-2 right-2 bg-orange-400 text-white font-bold px-3 py-1 rounded-full text-sm">
            R$ <?php echo number_format($raspadinha['preco'], 2, ',', '.'); ?>
          </div>

          <?php if ($raspadinha['imagem'] && file_exists($raspadinha['imagem'])): ?>
            <div class="mb-4">
              <img src="<?php echo htmlspecialchars($raspadinha['imagem']); ?>"
                   alt="<?php echo htmlspecialchars($raspadinha['nome']); ?>"
                   class="w-20 h-20 mx-auto rounded-lg object-cover">
            </div>
          <?php else: ?>
            <div class="mb-4">
              <i class="fas fa-diamond text-4xl text-blue-500"></i>
            </div>
          <?php endif; ?>

          <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($raspadinha['nome']); ?></h2>
          <p class="mt-2 text-gray-600">Prêmio Máximo: <span class="text-green-600 font-bold">R$ <?php echo number_format($raspadinha['premio_maximo'], 2, ',', '.'); ?></span></p>
          <p class="text-gray-600 mb-4">Chance: <span class="text-blue-600 font-bold"><?php echo number_format($raspadinha['probabilidade_premio'], 2, ',', '.'); ?>%</span></p>

          <a href="game.php?raspadinha_id=<?php echo $raspadinha['id']; ?>"
             class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-full transition-colors duration-300">
            <i class="fas fa-play mr-2"></i>JOGAR
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Link para voltar -->
  <div class="mt-8">
    <a href="index.php" class="text-blue-600 hover:text-blue-800 font-semibold">
      <i class="fas fa-arrow-left mr-2"></i>Voltar ao Início
    </a>
  </div>
</body>
</html>

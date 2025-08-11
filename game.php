<?php
require 'includes/db.php';
require 'includes/auth.php';

// Pega os parâmetros da URL
$raspadinha_id = isset($_GET['raspadinha_id']) ? intval($_GET['raspadinha_id']) : 0;

// Busca a raspadinha no banco de dados
$stmt = $conn->prepare("SELECT * FROM raspadinhas WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $raspadinha_id);
$stmt->execute();
$result = $stmt->get_result();
$raspadinha = $result->fetch_assoc();

if (!$raspadinha) {
    // Se não encontrou a raspadinha, redireciona para a página principal
    header("Location: raspadinhas.php");
    exit();
}

$userId = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$saldo = $usuario['balance'];

// Verifica se tem saldo suficiente
if ($saldo < $raspadinha['preco']) {
    $erro_saldo = true;
} else {
    $erro_saldo = false;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raspadinha - <?php echo htmlspecialchars($raspadinha['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-green-400 to-blue-500 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-diamond text-blue-500"></i>
                <?php echo htmlspecialchars($raspadinha['nome']); ?>
            </h1>
            <div class="bg-green-100 rounded-lg p-3 mb-4">
                <p class="text-green-800 font-semibold">Seu Saldo: R$ <?php echo number_format($saldo, 2, ',', '.'); ?></p>
            </div>
        </div>

        <!-- Raspadinha Info -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <?php if ($raspadinha['imagem'] && file_exists($raspadinha['imagem'])): ?>
                <img src="<?php echo htmlspecialchars($raspadinha['imagem']); ?>"
                     alt="<?php echo htmlspecialchars($raspadinha['nome']); ?>"
                     class="w-24 h-24 mx-auto mb-4 rounded-lg object-cover">
            <?php else: ?>
                <div class="w-24 h-24 mx-auto mb-4 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-diamond text-4xl text-blue-500"></i>
                </div>
            <?php endif; ?>

            <div class="space-y-2 text-sm">
                <p><span class="font-semibold">Preço:</span> R$ <?php echo number_format($raspadinha['preco'], 2, ',', '.'); ?></p>
                <p><span class="font-semibold">Prêmio Máximo:</span> R$ <?php echo number_format($raspadinha['premio_maximo'], 2, ',', '.'); ?></p>
                <p><span class="font-semibold">Chance de Ganhar:</span> <?php echo number_format($raspadinha['probabilidade_premio'], 2, ',', '.'); ?>%</p>
            </div>
        </div>
        <!-- Game Area -->
        <div id="game-area" class="mb-6">
            <?php if ($erro_saldo): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Saldo insuficiente para jogar esta raspadinha!
                </div>
                <a href="deposit.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Depositar
                </a>
            <?php else: ?>
                <div id="scratch-card" class="bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-lg p-8 cursor-pointer hover:shadow-lg transition-shadow" onclick="jogarRaspadinha()">
                    <div class="text-center">
                        <i class="fas fa-hand-pointer text-4xl text-yellow-800 mb-4"></i>
                        <p class="text-yellow-800 font-bold text-lg">Clique para Raspar!</p>
                        <p class="text-yellow-700 text-sm mt-2">Boa sorte!</p>
                    </div>
                </div>

                <div id="result-area" class="hidden mt-6">
                    <div id="result-message" class="text-lg font-bold mb-4"></div>
                    <button onclick="jogarNovamente()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>Jogar Novamente
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="flex justify-center space-x-4">
            <a href="raspadinhas.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                <i class="fas fa-home mr-2"></i>Início
            </a>
        </div>
    </div>

    <script>
        let jogando = false;

        function jogarRaspadinha() {
            if (jogando) return;

            jogando = true;
            const scratchCard = document.getElementById('scratch-card');
            scratchCard.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin text-4xl text-yellow-800 mb-4"></i><p class="text-yellow-800 font-bold">Processando...</p></div>';

            // Faz a requisição para jogar
            fetch('jogar.php?raspadinha_id=<?php echo $raspadinha_id; ?>', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.erro,
                        confirmButtonColor: '#3085d6'
                    });
                    resetGame();
                } else {
                    mostrarResultado(data);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro de conexão. Tente novamente.',
                    confirmButtonColor: '#3085d6'
                });
                resetGame();
            });
        }

        function mostrarResultado(data) {
            const scratchCard = document.getElementById('scratch-card');
            const resultArea = document.getElementById('result-area');
            const resultMessage = document.getElementById('result-message');

            if (data.ganhou) {
                scratchCard.innerHTML = `
                    <div class="text-center bg-green-100 p-6 rounded-lg">
                        <i class="fas fa-trophy text-6xl text-yellow-500 mb-4"></i>
                        <p class="text-green-800 font-bold text-xl">PARABÉNS!</p>
                        <p class="text-green-700">Você ganhou R$ ${data.premio}</p>
                    </div>
                `;
                resultMessage.innerHTML = `<span class="text-green-600">${data.mensagem}</span>`;

                // Atualiza o saldo na tela
                updateBalance(data.saldo);

                // Mostra confetes
                Swal.fire({
                    icon: 'success',
                    title: 'Parabéns!',
                    text: data.mensagem,
                    confirmButtonColor: '#10B981'
                });
            } else {
                scratchCard.innerHTML = `
                    <div class="text-center bg-red-100 p-6 rounded-lg">
                        <i class="fas fa-times-circle text-6xl text-red-500 mb-4"></i>
                        <p class="text-red-800 font-bold text-xl">Que pena!</p>
                        <p class="text-red-700">Tente novamente!</p>
                    </div>
                `;
                resultMessage.innerHTML = `<span class="text-red-600">${data.mensagem}</span>`;

                // Atualiza o saldo na tela
                updateBalance(data.saldo);
            }

            resultArea.classList.remove('hidden');
            jogando = false;
        }

        function updateBalance(novoSaldo) {
            // Atualiza o saldo exibido na página
            const saldoElement = document.querySelector('.bg-green-100 p');
            if (saldoElement) {
                saldoElement.innerHTML = `Seu Saldo: R$ ${novoSaldo}`;
            }
        }

        function resetGame() {
            const scratchCard = document.getElementById('scratch-card');
            scratchCard.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-hand-pointer text-4xl text-yellow-800 mb-4"></i>
                    <p class="text-yellow-800 font-bold text-lg">Clique para Raspar!</p>
                    <p class="text-yellow-700 text-sm mt-2">Boa sorte!</p>
                </div>
            `;
            document.getElementById('result-area').classList.add('hidden');
            jogando = false;
        }

        function jogarNovamente() {
            resetGame();
        }
    </script>
</body>
</html>


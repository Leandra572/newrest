<?php
session_start();
require 'includes/db.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['erro' => 'Faça login novamente.']);
  exit;
}

$userId = $_SESSION['usuario_id'];

// Pega o ID da raspadinha
$raspadinha_id = isset($_GET['raspadinha_id']) ? intval($_GET['raspadinha_id']) : 0;

// Busca a raspadinha no banco de dados
$stmt = $conn->prepare("SELECT * FROM raspadinhas WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $raspadinha_id);
$stmt->execute();
$result = $stmt->get_result();
$raspadinha = $result->fetch_assoc();

if (!$raspadinha) {
    echo json_encode(['erro' => 'Raspadinha não encontrada ou inativa.']);
    exit;
}

// Usa os dados da raspadinha do banco
$valorAposta = $raspadinha['preco'];
$premioMaximo = $raspadinha['premio_maximo'];
$chance = $raspadinha['probabilidade_premio'] / 100; // Converte porcentagem para decimal

// Pega saldo atual
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$saldoAtual = floatval($user['balance']);

// Verifica saldo suficiente
if ($saldoAtual < $valorAposta) {
  echo json_encode(['erro' => 'Saldo insuficiente.']);
  exit;
}

// Desconta aposta imediatamente
$novoSaldo = $saldoAtual - $valorAposta;
$stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
$stmt->bind_param("di", $novoSaldo, $userId);
$stmt->execute();

// Verifica config do prêmio forçado
$config = $conn->query("SELECT * FROM raspadinha_config LIMIT 1")->fetch_assoc();

$premioForcadoAtivo = $config && intval($config['ativo']) === 1;
$maxPremios = intval($config['max_premios']);
$premiosPagos = intval($config['premios_pagos']);
$valorPremio = floatval($config['valor_premio']);

$allSimbolos = ['maça.png', 'banana.png', 'uva.png', 'laranja.png', 'abacaxi.png', 'morango.png'];
shuffle($allSimbolos);
$simbolosSorteados = array_slice($allSimbolos, 0, 6);


// Decide se ganha ou não
$ganhou = false;
$mensagem = 'Que pena, tente de novo!';
$valorGanho = 0;

// Se manipulado pelo admin e ainda não atingiu o limite de prêmios
if ($premioForcadoAtivo && $premiosPagos < $maxPremios) {
  $ganhou = true;
  $valorGanho = $valorPremio;
  $mensagem = "🎉 Parabéns! Você ganhou R$ " . number_format($valorPremio, 2, ',', '.');

  // Atualiza saldo com prêmio
  $novoSaldo += $valorPremio;
  $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
  $stmt->bind_param("di", $novoSaldo, $userId);
  $stmt->execute();

  // Incrementa contador de prêmios pagos
  $conn->query("UPDATE raspadinha_config SET premios_pagos = premios_pagos + 1");
} else {
  // Sorteio aleatório com base na chance configurada
  if (mt_rand(0, 1000000) / 1000000 <= $chance) {
    $ganhou = true;
    $valorGanho = $premioMaximo;
    $mensagem = "🎉 Parabéns! Você ganhou R$ " . number_format($valorGanho, 2, ',', '.');

    $novoSaldo += $valorGanho;
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $novoSaldo, $userId);
    $stmt->execute();
  }
}

// Registra a jogada na tabela raspadinha_jogadas
try {
    $stmt = $conn->prepare("INSERT INTO raspadinha_jogadas (raspadinha_id, usuario_id, ganhou, premio, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiid", $raspadinha_id, $userId, $ganhou, $valorGanho);
    $stmt->execute();
} catch (Exception $e) {
    // Log do erro, mas não interrompe o fluxo
    error_log("Erro ao registrar jogada: " . $e->getMessage());
}

// Resposta JSON para o frontend
echo json_encode([
  'simbolos' => $simbolosSorteados,
  'ganhou' => $ganhou,
  'premio' => $valorGanho,
  'mensagem' => $mensagem,
  'saldo' => number_format($novoSaldo, 2, ',', '.')
]);

exit;?>
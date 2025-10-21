<?php
require 'conexao.php';

echo "=== Testando inserção de profissional ===\n";

try {
    // Inserir um profissional de teste
    $conn->begin_transaction();
    
    $stmt = $conn->prepare('INSERT INTO salao_profissionais (nome, foto, ativo, ordem) VALUES (?, ?, ?, ?)');
    $nome = 'Maria Silva';
    $foto = null;
    $ativo = 1;
    $ordem = 1;
    
    $stmt->bind_param('ssii', $nome, $foto, $ativo, $ordem);
    
    if ($stmt->execute()) {
        $profissionalId = $conn->insert_id;
        echo "✅ Profissional inserido com ID: $profissionalId\n";
        
        // Buscar serviços para associar
        $resultServicos = $conn->query('SELECT id FROM salao_servicos WHERE ativo = 1 LIMIT 2');
        if ($resultServicos && $resultServicos->num_rows > 0) {
            $stmtServico = $conn->prepare('INSERT INTO salao_profissional_servicos (profissional_id, servico_id) VALUES (?, ?)');
            
            while ($servico = $resultServicos->fetch_assoc()) {
                $servicoId = (int) $servico['id'];
                $stmtServico->bind_param('ii', $profissionalId, $servicoId);
                
                if ($stmtServico->execute()) {
                    echo "✅ Serviço $servicoId associado ao profissional\n";
                } else {
                    echo "❌ Erro ao associar serviço $servicoId\n";
                }
            }
            
            $stmtServico->close();
        }
        
        $conn->commit();
        echo "✅ Transação concluída com sucesso\n";
        
    } else {
        echo "❌ Erro ao inserir profissional: " . $stmt->error . "\n";
        $conn->rollback();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Erro na transação: " . $e->getMessage() . "\n";
}

// Verificar o resultado
echo "\n=== Verificando dados inseridos ===\n";

$result = $conn->query('
    SELECT p.id, p.nome, p.ativo, 
           GROUP_CONCAT(s.nome SEPARATOR ", ") as servicos
    FROM salao_profissionais p
    LEFT JOIN salao_profissional_servicos ps ON p.id = ps.profissional_id
    LEFT JOIN salao_servicos s ON ps.servico_id = s.id
    GROUP BY p.id, p.nome, p.ativo
');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | Nome: {$row['nome']} | Ativo: {$row['ativo']} | Serviços: " . ($row['servicos'] ?: 'Nenhum') . "\n";
    }
} else {
    echo "❌ Erro na consulta de verificação\n";
}

$conn->close();
?>
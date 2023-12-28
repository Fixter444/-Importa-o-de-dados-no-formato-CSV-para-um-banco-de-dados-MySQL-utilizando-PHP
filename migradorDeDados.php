<?php
$servername = "192.168.0.1";
$username = "usuario_banco";
$password = "senha_banco";
$dbname = "base_banco";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// Seta a condificação de caracteres UTF8, data e hora atual
	$conn->exec("SET NAMES 'utf8'");
	setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
	date_default_timezone_set('America/Sao_Paulo');
	
	echo "########################################################\n";
	echo "            Conexão realizada com sucesso!\n";
	echo "########################################################\n";

    # Se o arquivo CSV estiver em um outro repositório deverá modificar o caminho, se não deixe no
    # mesmo repositório
    # Exemplo: $csvFile = 'Desktop/nome_do_arquivo.csv';

	// Caminho para o arquivo CSV
    $csvFile = 'nome_do_arquivo.csv';
	
		// Abre o arquivo CSV
		if (($handle = fopen($csvFile, 'r')) !== false) 
		{	
			$firstLine = true; // Variável para controlar a primeira linha
			$id = 0;
			
			// Lê cada linha do arquivo CSV e salva na variável $data
            // Lembre-se de modificar o separador de colunas, se necessário, estamos utilizando o ';'.
			while (($data = fgetcsv($handle, 1000, ';')) !== false)
				{
					if ($firstLine)
						{
							$firstLine = false;
							continue; // Pular a primeira linha
						}
					
					// Adiciona mais um para a coluna 'id' da tabela, caso não for auto increment
					$id++;
				
					// Abre o aruivo texto para escrita dos dados que onde nenhum cliente foi encontrado no banco de dados
					$file = fopen('insertsColoqueNomeQueDeseja.txt', 'a');

                    // Abre o arquivo de texto para escrita quando ocorrer algum erro no insert
                    $file2 = fopen('insertsColoqueNomeQueDeseja.txt', 'a');


                    // Acessa a posição da coluna 1, as colunas começam em 0 (Zero)
					$parametro = trim($data[1]); // Você pode realizar outro tratamento de dados caso necessário
					$parametro2 = trim($data[1]);
					
					// Prepara a consulta SQL com o parâmetro
					$sqlConsulta = "SELECT id FROM clientes WHERE nome LIKE :parametro";
					$stmtCon = $conn->prepare($sqlConsulta);
					$stmtCon->bindParam(':parametro', $parametro);
					$stmtCon->execute();
			
                    // Verifica se algum resultado foi encontrado
					if ($stmtCon->rowCount() > 0) 
					{ 
					// Processa os resultados da consulta
					while ($row = $stmtCon->fetch(PDO::FETCH_ASSOC)) 
					{
						    // Exibe no terminal os dados que foram encontrados
							echo $id . ' - ' . 'Nome: ' . $data[1] . ' ID Cliente: ' . $row['id'] . " " . " (". $parametro .")" ."\n";
							$cliente_id = $row['id'];
							$idcli = $cliente_id;
						
					}
					} 
					else 
					{
						echo "##################################################################################################.\n";
						echo "Nenhum resultado encontrado.\n";
						echo $id . ' - ' . 'Nome: ' . $data[1] . ' ID Financeiros: ' . $idcli . " " . " (". $parametro2 .")" ."\n";
						echo "##################################################################################################.\n";
						// Escreve a instrução INSERT no arquivo de texto
						fwrite($file, $id . ' - ' . 'Nome: ' . $data[1] . ' ID Financeiros: ' . $idcli . " " . " (". $parametro2 .")" ."\n");
						readline();
						continue;
					} 


					$parametro3 = $cliente_id;
					
					// Prepara a consulta SQL com o parâmetro
					$sqlConsulta = "SELECT id FROM contrato WHERE idCliente LIKE :parametro3";
					$stmtCon = $conn->prepare($sqlConsulta);
					$stmtCon->bindParam(':parametro3', $parametro3);
					$stmtCon->execute();

					// Processa os resultados da consulta
					while ($row = $stmtCon->fetch(PDO::FETCH_ASSOC)) 
					{
						echo $id . ' - ' . 'Nome: ' . $data[1] . ' ID Contrato: ' . $row['id'] . " " ."(". $parametro .")" ."\n";
						$idContrato = $row['id'];
						
					}


					// Seta o id original do financeiro que está na posição 0 (zero) do CSV
					$id_original = $data[0];

					$criacao = date("Y/m/d,H:i:s");
					
					try 
					{	
						// Insere os dados no banco de dados na tabela cliente
						$sql = 'INSERT INTO financeiro (id, id_original, idContrato, criacao) VALUES (:id, :id_original, :idContrato, :criacao)';
					   
						$stmt = $conn->prepare($sql);
						
						$stmt->bindParam(':id', $id);
						$stmt->bindParam(':id_original', $id_original);
						$stmt->bindParam(':idContrato', $idContrato);
						$stmt->bindParam(':criacao', $criacao);

                        // Executa o comando de insert no banco de dados
						$stmt->execute();

					} 
					catch (Exception $e) 
					{
						
						// Obtém os valores dos registros do INSERT fornecido
						$values = array(
							'id' => $id,
							'id_original' => $id_original,
							'idContrato' => $idContrato,
							'criacao' => $criacao

						);
						
						// Cria o código de inserção com os valores obtidos
						$insertCode = 'INSERT INTO financeiro (id, id_original, idContrato, criacao) VALUES (';
						
						$valuesString = '';
						foreach ($values as $value) {
							$valuesString .= "'" . $value . "', ";
						}
						$valuesString = rtrim($valuesString, ", ");  // Remove a vírgula extra no final
						
						$insertCode .= $valuesString . ')';
						
						// Write the INSERT statement to the text file
						fwrite($file2, $insertCode . ";\n");
						
						// Exibe a mensagem de erro e continua a execução
						echo 'Ocorreu um erro ao inserir os dados: ' . $e->getMessage() . "\n";
						echo "##########################################################"."\n";
						echo "Falha ao inserir Financeiro: ". $id . ' - ' . $data[0]."\n";
						echo "Precione ENTER para continuar!"."\n";
						echo "##########################################################"."\n";

                        // Quando occorre um erro de inserção ele para e pede para precionar a tecla 'Enter'
						readline();

                        // Continua na próxima linha para inserção de dados
						continue;
					}
				
				} 
            // Fecha o arquivo de texto
		    fclose($file);
            fclose($file2);

        // Fecha o arquivo CSV
        fclose($handle);
    }
	
	echo "########################################################\n";
	echo "       Importação do CSV concluída com sucesso!\n";
	echo "########################################################\n";
    

} catch(PDOException $e) {
    echo "Error na conexão: " . $e->getMessage();
}

?>
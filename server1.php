<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");


require 'vendor/autoload.php';
require __DIR__ . '/src/Router.php';

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server("0.0.0.0",3000);

$databaseConfig = [
    'host' => 'localhost',
    'user' => 'mateus',
    'password' => 'Mm@#231296',
    'database' => 'articlesTable',
];

try {
    $db = new PDO(
        "mysql:host={$databaseConfig['host']};dbname={$databaseConfig['database']}",
        $databaseConfig['user'],
        $databaseConfig['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    exit("Erro no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    exit("Server Error: " . $e->getMessage());
}



$router = new Router();

$router->get('/get', function (Request $request, Response $response) use ($db) {
    try {
        $response->header('Content-Type', 'application/json');

        // Verifique se a conexão com o banco de dados foi estabelecida
        if (!$db) {
            throw new PDOException("Falha na conexão com o banco de dados.");
        }

        $query = $db->query("SELECT * FROM articles");

        // Verifique se a consulta foi executada com sucesso
        if ($query === false) {
            throw new PDOException("Erro na execução da consulta SQL.");
        }

        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        $response->write(json_encode(['status' => 201, 'data' => $result]));
        $response->end();
    } catch (PDOException $e) {
        $response->status(500);
        $response->write("Erro no banco de dados: " . $e->getMessage());
        $response->end();
    }
});


$router->get('/find/{id}', function (Request $request, Response $response) use ($db) {
    try {
        $response->header('Content-Type', 'application/json');

        // Adicione um ponto de depuração para verificar o valor do parâmetro {id}
        $id = $request->getAttribute('id');
        var_dump($id);

        if ($id !== null) {
            $query = $db->prepare("SELECT * FROM articles WHERE id = ?");
            $query->execute([$id]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            // Adicione um ponto de depuração para verificar o resultado da consulta
            var_dump($result);

            if ($result !== false) {
                $response->status(200);
                $response->write(json_encode(['status' => 200, 'data' => $result]));
            } else {
                $response->status(404); // Not Found
                $response->write(json_encode(['status' => 404, 'message' => 'Registro não encontrado']));
            }
        } else {
            $response->status(400); // Bad Request
            $response->write(json_encode(['status' => 400, 'message' => 'Parâmetro {id} ausente ou inválido na URL']));
        }
    } catch (PDOException $e) {
        // Adicione um ponto de depuração para verificar erros de banco de dados
        var_dump($e->getMessage());

        $response->status(500);
        $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
    } finally {
        // Adicione um ponto de depuração para verificar se a execução atinge este ponto
        var_dump("Finalizando");

        $response->end();
    }
});




// Rota para arquivos estáticos 
$router->get('/static/{file}', function (Request $request, Response $response) {
    $filename = '';
    if (isset($request->get['file'])) {
        $filename = __DIR__ . '/public/' . $request->get['file'];
    }
    
    if (file_exists($filename)) {
        $response->header('Content-Type', mime_content_type($filename));
        $response->sendfile($filename);
    } else {
        $response->status(404);
        $response->end("Not Found");
    }
}); 

$router->post('/create', function (Request $request, Response $response) use ($db) {
    try {
        $data = $request->post; 

        // Verifica se os valores são válidos
        $requiredFields = ['name', 'article_body', 'author', 'author_avatar'];
        if (array_diff($requiredFields, array_keys($data)) === []) {
            
            if (array_filter($data) === $data) {
                $insertQuery = $db->prepare("INSERT INTO articles (name, article_body, author, author_avatar) VALUES (?, ?, ?, ?)");
                $insertQuery->execute([$data['name'], $data['article_body'], $data['author'], $data['author_avatar']]);

                $response->header('Content-Type', 'application/json; charset=utf-8');
                $response->write(json_encode(['status' => 201, 'message' => 'Registro criado com sucesso']));
            } else {
                $response->status(400); // Bad Request
                $response->write(json_encode(['status' => 400, 'message' => 'Os valores não podem estar vazios']));
            }
        } else {
            $response->status(400); // Bad Request
            $response->write(json_encode(['status' => 400, 'message' => 'Parâmetros inválidos']));
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
    } finally {
        $response->end();
    }
});



$router->post('/update/{id}', function (Request $request, Response $response, $id) use ($db) {
    try {
        $data = json_decode($request->rawContent(), true);
        

        // Verifica se a decodificação foi bem-sucedida
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro na decodificação JSON: ' . json_last_error_msg());
        }

        // Verifica se os dados são válidos
        $requiredFields = ['name', 'article_body', 'author', 'author_avatar'];
        if (array_diff($requiredFields, array_keys($data)) === []) {
            // Verifica se o registro existe antes de atualizar
            $checkExistenceQuery = $db->prepare("SELECT id FROM articles WHERE id = ?");
            $checkExistenceQuery->execute([$id]);
            $existingRecord = $checkExistenceQuery->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                $updateQuery = $db->prepare("UPDATE articles SET name = ?, article_body = ?, author = ?, author_avatar = ? WHERE id = ?");
                $updateQuery->execute([$data['name'], $data['article_body'], $data['author'], $data['author_avatar'], $id]);

                $response->json(['status' => 200, 'message' => 'Registro atualizado com sucesso']);
            } else {
                $response->status(404); // Not Found
                $response->json(['status' => 404, 'message' => 'Registro não encontrado']);
            }
        } else {
            $response->status(400); // Bad Request
            $response->json(['status' => 400, 'message' => 'Parâmetros inválidos']);
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->json(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $response->status(400); // Bad Request
        $response->json(['status' => 400, 'message' => 'Erro na solicitação: ' . $e->getMessage()]);
    }
});



// excluir um registro
$router->post('/delete/{id}', function (Request $request, Response $response, $id) use ($db) {
    try {
        
        $checkExistenceQuery = $db->prepare("SELECT id FROM articles WHERE id = ?");
        $checkExistenceQuery->execute([$id]);
        $existingRecord = $checkExistenceQuery->fetch(PDO::FETCH_ASSOC);
       
        if ($existingRecord) {
            $deleteQuery = $db->prepare("DELETE FROM articles WHERE id = ?");
            $deleteQuery->execute([$id]);
        
            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 200, 'message' => 'Registro excluído com sucesso']));
        } else {
            $response->status(404); // erro
            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 404, 'message' => 'Registro não encontrado']));
        }
        
       
       


// Rota para criar um usuário
$router->post('/create-user', function (Request $request, Response $response) use ($db) {
    try {
        $data = $request->post;

        // Verifica se os valores são válidos
        $requiredFields = ['email', 'password'];
        if (array_diff($requiredFields, array_keys($data)) === []) {
            // Verifica se os valores não estão vazios
            if (array_filter($data) === $data) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                $insertQuery = $db->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                $insertQuery->execute([$data['email'], $hashedPassword]);

                $response->header('Content-Type', 'application/json; charset=utf-8');
                $response->write(json_encode(['status' => 201, 'message' => 'Usuário criado com sucesso']));
            } else {
                $response->status(400); // Bad Request
                $response->write(json_encode(['status' => 400, 'message' => 'Os valores não podem estar vazios']));
            }
        } else {
            $response->status(400); // Bad Request
            $response->write(json_encode(['status' => 400, 'message' => 'Parâmetros inválidos']));
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
    } finally {
        $response->end();
    }
});

//verificar e autenticar usuário por e-mail e senha
$router->post('/verify-user', function (Request $request, Response $response) use ($db) {
    try {
        $data = $request->post;

        $requiredFields = ['email', 'password'];
        if (array_diff($requiredFields, array_keys($data)) === []) {
            $query = $db->prepare("SELECT * FROM users WHERE email = ?");
            $query->execute([$data['email']]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'], $user['password'])) {
                $response->header('Content-Type', 'application/json; charset=utf-8');
                $response->write(json_encode(['status' => 200, 'message' => 'Usuário autenticado com sucesso']));
            } else {
                $response->status(401); // sem autorização
                $response->write(json_encode(['status' => 401, 'message' => 'Credenciais inválidas']));
            }
        } else {
            $response->status(400); // Bad Request
            $response->write(json_encode(['status' => 400, 'message' => 'Parâmetros inválidos']));
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
    } finally {
        $response->end();
    }
});



        if ($existingRecord) {
            $deleteQuery = $db->prepare("DELETE FROM articles WHERE id = ?");
            $deleteQuery->execute([$id]);

            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 200, 'message' => 'Registro excluído com sucesso']));
        } else {
            $response->status(404); // erro
            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 404, 'message' => 'Registro não encontrado']));
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->write("Erro no banco de dados: " . $e->getMessage());
    } finally {
        $response->end();
    }
});






$server->on("start", function (Server $server) {
    echo "OpenSwoole http server is started at http://0.0.0.0:3000\n";
});

$server->on(
    "request",
    function (Request $request, Response $response) use ($router) {
        try {
            $router->resolve($request, $response);
        } catch (Exception $e) {
            $response->status(500);
            $response->end("Server Error: " . $e->getMessage());
        }
    }
);

$server->start();

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

$server = new Server("0.0.0.0", 80);

$databaseConfig = [
    'host' => 'localhost',
    'user' => 'mateus',
    'password' => 'Mm@#231296',
    'database' => 'articlesTable',
];

// Conexão com o banco de dados
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

$server->on(
    "request",
    function (Request $request, Response $response) use ($router, $db) {
        try {
            $router->get('/get', function (Request $request, Response $response) use ($db) {
                try {
                    $response->header('Content-Type', 'application/json');

                    // Verifique se a conexão com o banco
                    if (!$db) {
                        throw new PDOException("Falha na conexão com o banco de dados.");
                    }

                    $query = $db->query("SELECT * FROM articles");

                    // Verifique se a consulta foi com sucesso
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


            $router->get('/find', function (Request $request, Response $response) use ($db) {
                try {
                    $query = $request->get;

                    if (!isset($query['id'])) {
                        $response->status(400); 
                        $response->write(json_encode(['status' => 400, 'message' => 'Parâmetro {id} ausente na URL']));
                        return;
                    }

                    $id = $query['id'];

                    $query = $db->prepare("SELECT * FROM articles WHERE id = ?");
                    $query->execute([$id]);
                    $result = $query->fetch(PDO::FETCH_ASSOC);


                    if ($result !== false) {
                        $response->status(200);
                        $response->write(json_encode(['status' => 200, 'data' => $result]));
                    } else {
                        $response->status(404); 
                        $response->write(json_encode(['status' => 404, 'message' => 'Registro não encontrado']));
                    }

                    $response->end();
                } catch (PDOException $e) {
                           
                    $response->status(500);
                    $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
                    $response->end();
                } finally {
                    
                }
            });
            
            
            

            $router->post('/create', function (Request $request, Response $response) use ($db) {
                try {
                    $data = $request->post;

                    
                    $requiredFields = ['name', 'article_body', 'author', 'author_avatar', 'idUsers'];
                    if (array_diff($requiredFields, array_keys($data)) === []) {

                        if (array_filter($data) === $data) {
                            $insertQuery = $db->prepare("INSERT INTO articles (name, article_body, author, author_avatar, idUsers) VALUES (?, ?, ?, ?, ?)");
                            $insertQuery->execute([$data['name'], $data['article_body'], $data['author'], $data['author_avatar'], $data['idUsers']]);
            
                            $response->header('Content-Type', 'application/json; charset=utf-8');
                            $response->write(json_encode(['status' => 201, 'sucess' => 'OK', 'message' => 'Registro criado com sucesso']));
                        } else {
                            $response->status(400); 
                            $response->write(json_encode(['status' => 400, 'message' => 'Os valores não podem estar vazios']));
                        }
                    } else {
                        $response->status(400); 
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


            $router->post('/update', function (Request $request, Response $response) use ($db) {
                try {
                    $data = $request->post;
                    $id = $data['id'] ?? null;

                   
                    if ($id !== null) {
                        $checkExistenceQuery = $db->prepare("SELECT id FROM articles WHERE id = ?");
                        $checkExistenceQuery->execute([$id]);
                        $existingRecord = $checkExistenceQuery->fetch(PDO::FETCH_ASSOC);
            
                        
                        if ($existingRecord) {
                            $requiredFields = ['name', 'article_body', 'author', 'author_avatar'];

                            
                            if (array_diff($requiredFields, array_keys($data)) === []) {
                                $updateQuery = $db->prepare("UPDATE articles SET name = ?, article_body = ?, author = ?, author_avatar = ? WHERE id = ?");
                                $updateQuery->execute([$data['name'], $data['article_body'], $data['author'], $data['author_avatar'], $id]);

                                $response->header('Content-Type', 'application/json');
                                $response->write(json_encode(['status' => 'OK', 'message' => 'Registro atualizado com sucesso']));
                            } else {
                                $response->status(400); 
                                $response->write(json_encode(['status' => 'ERRO', 'message' => 'Parâmetros inválidos']));
                            }
                        } else {
                            $response->status(404); 
                            $response->header('Content-Type', 'application/json');
                            $response->write(json_encode(['status' => 'ERRO', 'message' => 'Registro não encontrado']));
                        }
                    } else {
                        $response->status(400); 
                        $response->write(json_encode(['status' => 'ERRO', 'message' => 'Parâmetro {id} ausente ou inválido na requisição']));
                    }

                    $response->end();
                } catch (PDOException $e) {
                    $response->status(500);
                    $response->write(json_encode(['status' => 'ERRO', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
                } finally {
                    if (!$response->ended) {
                        $response->end();
                    
                }
                };


            $router->post('/delete', function (Request $request, Response $response) use ($db) {
                try {
                    $data = $request->post;
                    $id = $data['id'] ?? null;
            
                    // Verifica se o ID foi fornecido na requisição
                    if ($id !== null) {
                        var_dump("ID fornecido: " . $id);
            
                        $checkExistenceQuery = $db->prepare("SELECT id FROM articles WHERE id = ?");
                        $checkExistenceQuery->execute([$id]);
                        $existingRecord = $checkExistenceQuery->fetch(PDO::FETCH_ASSOC);
            
                        // Verifica se o registro existe antes de excluir
                        if ($existingRecord) {
                            var_dump("Registro encontrado para exclusão");
            
                            $deleteQuery = $db->prepare("DELETE FROM articles WHERE id = ?");
                            $deleteQuery->execute([$id]);

                            var_dump("Registro excluído com sucesso");
            
                            $response->header('Content-Type', 'application/json');
                            $response->write(json_encode(['status' => 'OK', 'message' => 'Registro excluído com sucesso']));
                        } else {
                            var_dump("Registro não encontrado para exclusão");

                            $response->status(404); // Not Found
                            $response->header('Content-Type', 'application/json');
                            $response->write(json_encode(['status' => 'ERRO', 'message' => 'Registro não encontrado']));
                        }
                    } else {
                        var_dump("ID não fornecido ou inválido");

                        $response->status(400); // Bad Request
                        $response->write(json_encode(['status' => 'ERRO', 'message' => 'Parâmetro {id} ausente ou inválido na requisição']));
                    }
                } catch (PDOException $e) {
                    var_dump("Erro no banco de dados: " . $e->getMessage());
            
                    $response->status(500);
                    $response->write(json_encode(['status' => 'ERRO', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
                } finally {
                    // Verifica se a resposta já foi encerrada
                    if (!$response->ended) {
                        var_dump("Finalizando resposta");
                        $response->end();
                    }
                }
            });
            
            
            
            

            // Rota para criar um usuário
            $router->post('/create-user', function (Request $request, Response $response) use ($db) {
                try {
                    $data = $request->post;

                    
                    $requiredFields = ['email', 'password', 'name'];
                    if (array_diff($requiredFields, array_keys($data)) === []) {
                        if (array_filter($data) === $data) {
                            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                            $insertQuery = $db->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
                            $insertQuery->execute([$data['email'], $hashedPassword, $data['name']]);

                            $response->header('Content-Type', 'application/json; charset=utf-8');
                            $response->write(json_encode(['status' => 201, 'sucess' => 'Usuário OK', 'message' => 'Usuário criado com sucesso']));
                        } else {
                            $response->status(400); 
                            $response->write(json_encode(['status' => 400, 'message' => 'Os valores não podem estar vazios']));
                        }
                    } else {
                        $response->status(400); 
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

            // Verificar e autenticar usuário por e-mail e senha
            $router->post('/verify-user', function (Request $request, Response $response) use ($db) {
                try {
                    $data = $request->post;
            
                    $requiredFields = ['email', 'password'];
                    if (array_diff($requiredFields, array_keys($data)) === []) {
                        $query = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
                        $query->execute([$data['email']]);
                        $user = $query->fetch(PDO::FETCH_ASSOC);
            
                        if ($user && password_verify($data['password'], $user['password'])) {
                            
                            unset($user['password']);
            
                            $response->header('Content-Type', 'application/json; charset=utf-8');
                            $response->write(json_encode(['status' => 200, 'success' => 'Usuário autenticado com sucesso', 'user' => $user]));
                        } else {
                            $response->status(401); 
                            $response->write(json_encode(['status' => 401, 'message' => 'Credenciais inválidas']));
                        }
                    } else {
                        $response->status(400); 
                        $response->write(json_encode(['status' => 400, 'message' => 'Parâmetros inválidos']));
                        $response->end();
                    }
                } catch (PDOException $e) {
                    $response->status(500);
                    $response->header('Content-Type', 'application/json');
                    $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
                } finally {
                    $response->end();
                }
            });
            

            $router->resolve($request, $response);
        } catch (Exception $e) {
            $response->status(500);
            $response->end("Server Error: " . $e->getMessage());
        }
    }
);

$server->on("start", function (Server $server) {
    echo "http server is started at http://0.0.0.0:80\n";
});

$server->start();
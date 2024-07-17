<?php 
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$app->post('/login', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $data = $request->getParsedBody();

    if (isset($data['username']) && isset($data['password'])) {
        $username = $data['username'];
        $password = $data['password'];

        try {
            $sql1 = "SELECT * FROM User WHERE username = :username";
            $sql2 = "SELECT * FROM Dentist WHERE username = :username";
            // $sql1 = "SELECT * FROM User WHERE username = ?";
            // $sql2 = "SELECT * FROM Dentist WHERE username = ?";

            $stmt1 = $conn->prepare($sql1);
            $stmt2 = $conn->prepare($sql2);

            $stmt1->bindValue(':username', $username);
            $stmt2->bindValue(':username', $username);

            $stmt1->execute();
            $stmt2->execute();

            // $stmt1->execute([$username]);
            // $stmt2->execute([$username]);

            $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
            $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($result1 || $result2) {
                if ($result1) {
                    $row = $result1;
                    $myid = $row['userID'];
                } elseif ($result2) {
                    $row = $result2;
                    $myid = $row['dentistID'];
                }

                $storedPassword = $row['password'];
                $mycategory = $row['category'];

                if (md5($password) === $storedPassword) {
                    $sec_key = '85ldofi';
                    $payload = array(
                        'iss' => 'localhost',
                        'aud' => 'localhost',
                        'username' => $username,
                        'category' => $mycategory,
                        'userID' => $myid,
                        'exp' => time() + 3600 // Token expires in 1 hour
                    );
                    $encode = JWT::encode($payload, $sec_key, 'HS256');
                    
                    $response->getBody()->write(json_encode(['token' => $encode]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                } else {
                    $error = ['error' => 'Incorrect password.'];
                    $response->getBody()->write(json_encode($error));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
                }
            } else {
                $error = ['username' => $username, 'password' => $password,'error' => 'No account found, please register one.'];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
        } catch (PDOException $e) {
            $error = ['error' => 'Database error: ' . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } else {
        $error = ['error' => 'Username and password are required.'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->post('/signup', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $data = $request->getParsedBody();

    if(isset($data['name']) && isset($data['age']) && isset($data['username']) && isset($data['password']) && isset($data['phone']) && isset($data['email']) && isset($data['gender'])) {
        $name = $data['name'];
        $age = $data['age'];
        $username = $data['username'];
        $password = $data['password'];
        $phone = $data['phone'];
        $email = $data['email'];
        $gender = $data['gender'];

        $encryptedpassword = md5($password);

        $sql1 = "SELECT * FROM User WHERE username = :username";
           
        $stmt1 = $conn->prepare($sql1);
        
        $stmt1->bindValue(':username', $username);

        $stmt1->execute();

        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);

            if ($result1) {
        
                $row = $result1;
                $myid = $row['userID'];

                $storedPassword = $row['password'];

                if (md5($password) === $storedPassword) {
                    $error = ['error' => 'Account already exists.'];
                    $response->getBody()->write(json_encode($error));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
                } else {
                    $error = ['error' => 'Username used.'];
                    $response->getBody()->write(json_encode($error));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
                }
            } else {
                $sql = "INSERT INTO User(name, age, username, password, email, phone, gender, category) VALUES
             (:name, :age, :username, :password, :email, :phone, :gender, 1)";
           

           $stmt = $conn->prepare($sql);
           $stmt->bindValue(':name', $name);
           $stmt->bindValue(':age', $age);
           $stmt->bindValue(':username', $username);
           $stmt->bindValue(':password', $encryptedpassword);
           $stmt->bindValue(':phone', $phone);
           $stmt->bindValue(':email', $email);
           $stmt->bindValue(':gender', $gender);
           $stmt->execute();

    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        

        // try {
            
    } else {
        $message = ['message' => 'All fields are required.'];
        $response->getBody()->write(json_encode($message));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

?>
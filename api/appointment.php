<?php
require_once './config.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$app->post('/addAppointment', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $data = $request->getParsedBody();

    // Validate the required fields
    if (isset($data['userID']) && isset($data['dentistID']) && isset($data['appointmentDate']) && isset($data['appointmentTime']) && isset($data['appointment_category'])) {
        $userID = $data['userID'];
        $dentistID = $data['dentistID'];
        $appointmentDate = $data['appointmentDate'];
        $appointmentTime = $data['appointmentTime'];
        $appointment_category = $data['appointment_category'];

        // Check if the appointment date is in the past
        $currentDate = date('Y-m-d');
        if ($appointmentDate < $currentDate) {
            $error = ['error' => 'The appointment date must be in the future.'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check if the appointment slot is already taken
            $sqlCheck = "SELECT * FROM appointment WHERE dentistID = :dentistID AND appointmentDate = :appointmentDate AND appointmentTime = :appointmentTime";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindValue(':dentistID', $dentistID);
            $stmtCheck->bindValue(':appointmentDate', $appointmentDate);
            $stmtCheck->bindValue(':appointmentTime', $appointmentTime);
            $stmtCheck->execute();

            if ($stmtCheck->rowCount() > 0) {
                $error = ['error' => 'This appointment slot is already taken.'];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Insert new appointment
            $sql = "INSERT INTO appointment (userID, dentistID, appointmentDate, appointmentTime, appointment_category) VALUES (:userID, :dentistID, :appointmentDate, :appointmentTime, :appointment_category)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $userID);
            $stmt->bindValue(':dentistID', $dentistID);
            $stmt->bindValue(':appointmentDate', $appointmentDate);
            $stmt->bindValue(':appointmentTime', $appointmentTime);
            $stmt->bindValue(':appointment_category', $appointment_category);
            $stmt->execute();

            $response->getBody()->write(json_encode(['message' => 'Appointment booked successfully.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (PDOException $e) {
            $error = ['error' => 'Database error: ' . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } else {
        $error = ['error' => 'All fields are required.'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->get('/user/{id}/appointments', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $userId = $args['id'];

    try {
        // Fetch user information
        $sqlUser = "SELECT * FROM user WHERE userID = :userID";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bindValue(':userID', $userId);
        $stmtUser->execute();
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = ['error' => 'User not found.'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Fetch appointments
        $sqlAppointments = "SELECT * FROM appointment WHERE userID = :userID";
        $stmtAppointments = $conn->prepare($sqlAppointments);
        $stmtAppointments->bindValue(':userID', $userId);
        $stmtAppointments->execute();
        $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);

        $result = [
            'user' => $user,
            'appointments' => $appointments
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (PDOException $e) {
        $error = ['error' => 'Database error: ' . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/dentist/{id}/appointments', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $dentistId = $args['id'];

    try {
        // Fetch user information
        $sqlUser = "SELECT * FROM dentist WHERE dentistID = :dentistID";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bindValue(':dentistID', $dentistId);
        $stmtUser->execute();
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = ['error' => 'User not found.'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Fetch appointments
        $sqlAppointments = "SELECT * FROM appointment WHERE dentistID = :dentistID";
        $stmtAppointments = $conn->prepare($sqlAppointments);
        $stmtAppointments->bindValue(':dentistID', $dentistId);
        $stmtAppointments->execute();
        $appointments = $stmtAppointments->fetchAll(PDO::FETCH_ASSOC);

        $result = [
            'user' => $user,
            'appointments' => $appointments
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (PDOException $e) {
        $error = ['error' => 'Database error: ' . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->delete('/appointment/{id}', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $appointmentId = $args['id'];

    try {
        // Delete the appointment
        $sql = "DELETE FROM appointment WHERE appointmentID = :appointmentID";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':appointmentID', $appointmentId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Appointment deleted successfully.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $error = ['error' => 'Appointment not found.'];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } catch (PDOException $e) {
        $error = ['error' => 'Database error: ' . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/appointment/{id}/comments', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $appointmentId = $args['id'];

    try {
        // Fetch comments for the specific appointment
        $sql = "SELECT descriptions FROM comment WHERE appointmentID = :appointmentID";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':appointmentID', $appointmentId);
        $stmt->execute();
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            $response->getBody()->write(json_encode($comment));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(['description' => '']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    } catch (PDOException $e) {
        $error = ['error' => 'Database error: ' . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/appointment/{id}/comments', function(Request $request, Response $response, $args) {
    $db = new db();
    $conn = $db->connect();
    $appointmentId = $args['id'];
    $data = $request->getParsedBody();

    if (!isset($data['description'])) {
        $error = ['error' => 'Description is required.'];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $description = $data['description'];

    try {
        // Check if comment exists
        $sqlCheck = "SELECT * FROM comment WHERE appointmentID = :appointmentID";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bindValue(':appointmentID', $appointmentId);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            // Update existing comment
            $sql = "UPDATE comment SET descriptions = :descriptions WHERE appointmentID = :appointmentID";
            $stmt = $conn->prepare($sql);
        } else {
            // Insert new comment
            $sql = "INSERT INTO comment (appointmentID, descriptions) VALUES (:appointmentID, :descriptions)";
            $stmt = $conn->prepare($sql);
        }

        $stmt->bindValue(':appointmentID', $appointmentId);
        $stmt->bindValue(':descriptions', $description);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Comment saved successfully.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (PDOException $e) {
        $error = ['error' => 'Database error: ' . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
?>

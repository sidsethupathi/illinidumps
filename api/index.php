<?php
require_once('../library.php');
require_once('connect.php');
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function.
 */
$app->config('debug', false);

// GET route
$app->get('/', function() {
    include('api_documentation.php');
    
});

$app->get('/locations', function () {
    $sql = "SELECT id, name, slug, image, address, votes, smell_score, crowd_score, clean_score, latitude, longitude FROM locations WHERE hidden=0";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $bathrooms = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"bathrooms": ' . json_encode($bathrooms) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
    
});


$app->get('/locations/search/:match', function ($match) {
    $sql = "SELECT id, name, slug, image, address, votes, smell_score, crowd_score, clean_score, latitude, longitude FROM locations WHERE name LIKE '%$match%' AND hidden=0";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $bathrooms = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"bathrooms": ' . json_encode($bathrooms) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
    
});

$app->get('/locations/close_to/:id/', function($id) {
    $sql = "select * from (select locations.id, locations.slug, locations.name, locations.score, locations.latitude, locations.longitude from distance left join locations on locations.id = distance.dest_id where src_id = :id order by distance.distance asc limit 5)t order by score desc";
    
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $bathrooms = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo '{"bathrooms": ' . json_encode($bathrooms) . '}';
     } catch(PDOException $e) {
        echo '{"error":{"text":' . $e.getMessage() . '}}';
     }
});

$app->get('/locations/close', function () {
    $request = \Slim\Slim::getInstance()->request();
    $data = $request->params();

    $sql = "SELECT *, POW(69.1 * (latitude - :latitude), 2) + POW(69.1 * (:longitude - longitude) * COS(latitude / 57.3), 2) AS distance FROM locations ORDER BY distance LIMIT 1";
    if(is_null($data)) {
        echo '{"error":{"text": "Incorrect parameters"}}';
    } else {
    try {
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        
        $db = getConnection();

        $stmt = $db->prepare($sql);
        $stmt->bindParam("latitude", $latitude);
        $stmt->bindParam("longitude", $longitude);

        $stmt->execute();

        $bathroom = $stmt->fetchObject();
        $db = null;
        echo json_encode($bathroom);
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
    }
    
});
$app->get('/locations/:id', function($id) {
    if(is_numeric($id)) {
        $sql = "SELECT * FROM locations WHERE id=:id";
    } else {
        $sql = "SELECT * FROM locations WHERE slug=:id";
    }
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $bathroom = $stmt->fetchObject();
        $db = null;
        echo json_encode($bathroom);
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

// remember to change the hidden parameter too
$app->get('/comments/:id', function($id) {
    $sql = "SELECT * FROM comments WHERE location_id=:id ORDER BY timestamp DESC";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $bathrooms = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo censor_string('{"comments": ' . json_encode($bathrooms) . '}');
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

// POST route
$app->post('/comments/:id', function ($id) {
    $request = \Slim\Slim::getInstance()->request();
    $comment_info = json_decode($request->getBody());
    $sql = "INSERT INTO comments (location_id, author, comment) VALUES (:lid, :author, :comment)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("lid", $id);
        $stmt->bindParam("author", $comment_info->author);
        $stmt->bindParam("comment", $comment_info->comment);
        if($stmt->execute()) {
            echo '{"status":"200"}';
        } else {
            echo '{"status":"400"}';
        }
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

// PUT route
$app->put('/locations/vote_main/:id', function ($id) {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $sql = "INSERT INTO votes (id, location_id, rating) VALUES ('NULL', :id, :rating)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->bindParam("rating", $data->rating);
        if($stmt->execute()) {
            $sql = "SELECT score, votes FROM locations WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $bathroom = $stmt->fetchObject();
            $last_score = $bathroom->score;
            $votes = $bathroom->votes;
            $votes++;
            switch($data->rating) {
                case 1:
                    $new_score = $last_score + 2;
                    break;
                case 2:
                    $new_score = $last_score + 1;
                    break;
                case 3:
                    $new_score = $last_score - 2;
                    break;
                default:
                    $new_score = $last_score;
                    break;
            }
            $sql = "UPDATE locations SET score=:score, votes=:votes WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->bindParam("score", $new_score);
            $stmt->bindParam("votes", $votes);
            if($stmt->execute()) {
                echo '{"status":"200"}';
            } else {
                echo '{"status":"400"}';
            }
        } else {
            echo '{"status":"400"}';
        }
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

$app->put('/locations/vote_extra/:id', function ($id) {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
	$sql = "INSERT INTO extra_votes (location_id, smell, clean, crowd) VALUES (:lid, :smell, :clean, :crowd)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("lid", $id);
        $stmt->bindParam("smell", $data->smelliness);
        $stmt->bindParam("clean", $data->cleanliness);
        $stmt->bindParam("crowd", $data->crowdedness);
        if($stmt->execute()) {
            $sql = "SELECT id, smell_score, smell_votes, clean_score, clean_votes, crowd_score, crowd_votes FROM locations WHERE id=:lid";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("lid", $id);
            $stmt->execute();
            $bathroom = $stmt->fetchObject();
            
            $smell = $bathroom->smell_score;
            $clean = $bathroom->clean_score;
            $crowd = $bathroom->crowd_score;

            $smell_votes = $bathroom->smell_votes;
            $clean_votes = $bathroom->clean_votes;
            $crowd_votes = $bathroom->crowd_votes;

            $sql = "UPDATE locations SET smell_score = :smell, smell_votes = :smell_v, clean_score = :clean, clean_votes = :clean_v, crowd_score = :crowd, crowd_votes = :crowd_v WHERE id = :lid";
            if($data->smelliness != 0) {
                $smell = updateAverage("smell", $id);
                $smell_votes++;
            }

            if($data->cleanliness != 0) {
                $clean = updateAverage("clean", $id);
                $clean_votes++;
            }

            if($data->crowdedness != 0) {
                $crowd = updateAverage("crowd", $id);
                $crowd_votes++;
            }
            $stmt = $db->prepare($sql);
            $stmt->bindParam("smell", $smell);
            $stmt->bindParam("clean", $clean);
            $stmt->bindParam("crowd", $crowd);
            $stmt->bindParam("smell_v", $smell_votes);
            $stmt->bindParam("clean_v", $clean_votes);
            $stmt->bindParam("crowd_v", $crowd_votes);
            $stmt->bindParam("lid", $id);
            if($stmt->execute()) {
                echo '{"status":"200"}';
            } else {
                echo '{"status":"400"}';
            }
        } else {
            echo '{"status":"400"}';
        }
    } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});

$app->put('/locations/:id', function ($id) {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $sql = "UPDATE locations SET latitude=:latitude, longitude=:longitude WHERE id=:id";
    try {
        $db = getMainConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->bindParam("latitude", $data->latitude);
        $stmt->bindParam("longitude", $data->longitude);
        if($stmt->execute()) {
                echo '{"status":"200"}';
            } else {
                echo '{"status":"400"}';
            } 
        } catch(PDOException $e) {
        echo '{"error":{"text":' . $e->getMessage() . '}}';
    }
});
/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();


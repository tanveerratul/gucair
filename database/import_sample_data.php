<?php
/**
 * Optional local helper: import database/sample_data.json into MongoDB.
 *
 * Run from the project root:
 *   php database/import_sample_data.php
 *
 * The website never loads this file automatically. It is only a lab helper
 * for filling an empty local database with sample documents.
 */

// Do not allow a web visitor to trigger an import.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';

/**
 * Convert an Extended JSON { "$oid": "..." } value to a BSON ObjectId.
 */
function sample_object_id($value)
{
    if (!is_array($value) || !isset($value['$oid'])) {
        return null;
    }

    $hex = (string) $value['$oid'];
    if (!preg_match('/^[a-f0-9]{24}$/i', $hex)) {
        return null;
    }

    return new MongoDB\BSON\ObjectId($hex);
}

/**
 * Convert an Extended JSON { "$date": "..." } value to a BSON UTCDateTime.
 */
function sample_utc_datetime($value)
{
    if (!is_array($value) || !isset($value['$date'])) {
        return null;
    }

    $timestamp = strtotime((string) $value['$date']);
    return $timestamp === false ? null : new MongoDB\BSON\UTCDateTime($timestamp * 1000);
}

$jsonPath = __DIR__ . '/sample_data.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, 'Missing sample_data.json.' . PHP_EOL);
    exit(1);
}

try {
    $sample = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
    $universities = get_universities_collection();
    $assessments = get_assessments_collection();

    $idMap = [];
    $addedUniversities = 0;
    $skippedUniversities = 0;

    foreach ($sample['universities'] ?? [] as $document) {
        $sourceId = sample_object_id($document['_id'] ?? null);
        $email = strtolower(trim((string) ($document['email'] ?? '')));

        if ($sourceId === null || $email === '') {
            $skippedUniversities++;
            continue;
        }

        // Re-running the importer should not create duplicate accounts.
        $existing = $universities->findOne(
            ['$or' => [['_id' => $sourceId], ['email' => $email]]],
            ['projection' => ['_id' => 1]]
        );

        if ($existing) {
            $idMap[(string) $sourceId] = $existing['_id'];
            $skippedUniversities++;
            continue;
        }

        $document['_id'] = $sourceId;
        $document['email'] = $email;
        $document['created_at'] = sample_utc_datetime($document['created_at'] ?? null)
            ?: new MongoDB\BSON\UTCDateTime();

        $result = $universities->insertOne($document);
        $idMap[(string) $sourceId] = $result->getInsertedId();
        $addedUniversities++;
    }

    $addedAssessments = 0;
    $skippedAssessments = 0;

    foreach ($sample['assessments'] ?? [] as $document) {
        $sourceId = sample_object_id($document['_id'] ?? null);
        $sourceUniversityId = sample_object_id($document['university_id'] ?? null);
        $createdAt = sample_utc_datetime($document['created_at'] ?? null);

        if ($sourceId === null || $sourceUniversityId === null || $createdAt === null) {
            $skippedAssessments++;
            continue;
        }

        $universityKey = (string) $sourceUniversityId;
        if (!isset($idMap[$universityKey])) {
            $skippedAssessments++;
            continue;
        }

        $realUniversityId = $idMap[$universityKey];

        // Match the same rule used on a repeated import.
        $alreadySaved = $assessments->countDocuments(
            ['$or' => [
                ['_id' => $sourceId],
                [
                    'university_id' => $realUniversityId,
                    'created_at'    => $createdAt,
                    'score'         => $document['score'] ?? null,
                ],
            ]],
            ['limit' => 1]
        ) > 0;

        if ($alreadySaved) {
            $skippedAssessments++;
            continue;
        }

        $document['_id'] = $sourceId;
        $document['university_id'] = $realUniversityId;
        $document['created_at'] = $createdAt;

        $assessments->insertOne($document);
        $addedAssessments++;
    }

    echo 'Universities added: ' . $addedUniversities . ', skipped: ' . $skippedUniversities . PHP_EOL;
    echo 'Assessments added: ' . $addedAssessments . ', skipped: ' . $skippedAssessments . PHP_EOL;
} catch (Throwable $error) {
    error_log('GUCAIR sample import failed: ' . $error->getMessage());
    fwrite(STDERR, 'The sample import failed. Check that MongoDB is running.' . PHP_EOL);
    exit(1);
}

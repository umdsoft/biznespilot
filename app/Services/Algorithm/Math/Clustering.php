<?php

namespace App\Services\Algorithm\Math;

/**
 * Clustering Algorithms (K-Means, DBSCAN)
 *
 * Mathematical clustering utilities for customer segmentation without AI.
 *
 * Research:
 * - MacQueen (1967) - K-Means clustering
 * - Lloyd (1982) - Least squares quantization
 * - Ester et al. (1996) - DBSCAN density-based clustering
 *
 * @version 1.0.0
 * @package App\Services\Algorithm\Math
 */
class Clustering
{
    /**
     * K-Means clustering algorithm
     *
     * Groups data points into K clusters by minimizing within-cluster variance.
     *
     * Algorithm:
     * 1. Initialize K centroids randomly
     * 2. Assign each point to nearest centroid
     * 3. Update centroids to mean of assigned points
     * 4. Repeat 2-3 until convergence
     *
     * @param array $data Array of data points (each point is an array of features)
     * @param int $k Number of clusters
     * @param int $maxIterations Maximum iterations
     * @param float $tolerance Convergence tolerance
     * @return array Cluster assignments and centroids
     */
    public static function kMeans(
        array $data,
        int $k = 3,
        int $maxIterations = 100,
        float $tolerance = 0.001
    ): array {
        if (empty($data) || $k < 1) {
            return [
                'clusters' => [],
                'centroids' => [],
                'iterations' => 0,
            ];
        }

        $n = count($data);
        $k = min($k, $n); // Can't have more clusters than points

        // Step 1: Initialize centroids randomly
        $centroids = self::initializeCentroids($data, $k);

        // Iterate
        $assignments = array_fill(0, $n, 0);
        $iterations = 0;

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $iterations++;
            $oldCentroids = $centroids;

            // Step 2: Assign points to nearest centroid
            for ($i = 0; $i < $n; $i++) {
                $minDistance = PHP_FLOAT_MAX;
                $closestCluster = 0;

                for ($j = 0; $j < $k; $j++) {
                    $distance = self::euclideanDistance($data[$i], $centroids[$j]);
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $closestCluster = $j;
                    }
                }

                $assignments[$i] = $closestCluster;
            }

            // Step 3: Update centroids
            $centroids = self::updateCentroids($data, $assignments, $k);

            // Check convergence
            $centroidShift = self::calculateCentroidShift($oldCentroids, $centroids);
            if ($centroidShift < $tolerance) {
                break; // Converged
            }
        }

        // Format results
        $clusters = self::formatClusters($data, $assignments, $k);

        return [
            'clusters' => $clusters,
            'centroids' => $centroids,
            'assignments' => $assignments,
            'iterations' => $iterations,
            'converged' => $iterations < $maxIterations,
        ];
    }

    /**
     * Initialize centroids using K-Means++ algorithm
     *
     * K-Means++ chooses initial centers that are far apart,
     * leading to better convergence.
     *
     * @param array $data Data points
     * @param int $k Number of clusters
     * @return array Initial centroids
     */
    protected static function initializeCentroids(array $data, int $k): array
    {
        $n = count($data);
        $centroids = [];

        // Choose first centroid randomly
        $firstIndex = rand(0, $n - 1);
        $centroids[] = $data[$firstIndex];

        // Choose remaining centroids
        for ($i = 1; $i < $k; $i++) {
            $distances = [];

            // Calculate distance to nearest existing centroid
            for ($j = 0; $j < $n; $j++) {
                $minDist = PHP_FLOAT_MAX;

                foreach ($centroids as $centroid) {
                    $dist = self::euclideanDistance($data[$j], $centroid);
                    $minDist = min($minDist, $dist);
                }

                $distances[$j] = $minDist * $minDist; // Squared distance
            }

            // Choose next centroid with probability proportional to distance
            $sumDistances = array_sum($distances);
            if ($sumDistances == 0) {
                // All points are at same location, pick random
                $nextIndex = rand(0, $n - 1);
            } else {
                $random = rand(0, (int)($sumDistances * 1000)) / 1000;
                $cumulative = 0;
                $nextIndex = 0;

                foreach ($distances as $idx => $dist) {
                    $cumulative += $dist;
                    if ($cumulative >= $random) {
                        $nextIndex = $idx;
                        break;
                    }
                }
            }

            $centroids[] = $data[$nextIndex];
        }

        return $centroids;
    }

    /**
     * Update centroids to mean of assigned points
     *
     * @param array $data Data points
     * @param array $assignments Cluster assignments
     * @param int $k Number of clusters
     * @return array Updated centroids
     */
    protected static function updateCentroids(array $data, array $assignments, int $k): array
    {
        $centroids = [];
        $dimensions = count($data[0]);

        for ($i = 0; $i < $k; $i++) {
            // Find all points in this cluster
            $clusterPoints = [];
            foreach ($assignments as $idx => $cluster) {
                if ($cluster === $i) {
                    $clusterPoints[] = $data[$idx];
                }
            }

            // Calculate mean
            if (empty($clusterPoints)) {
                // Empty cluster, keep old centroid or random point
                $centroids[$i] = $data[rand(0, count($data) - 1)];
            } else {
                $centroid = array_fill(0, $dimensions, 0);
                foreach ($clusterPoints as $point) {
                    for ($d = 0; $d < $dimensions; $d++) {
                        $centroid[$d] += $point[$d];
                    }
                }

                for ($d = 0; $d < $dimensions; $d++) {
                    $centroid[$d] /= count($clusterPoints);
                }

                $centroids[$i] = $centroid;
            }
        }

        return $centroids;
    }

    /**
     * Calculate Euclidean distance between two points
     *
     * @param array $point1 First point
     * @param array $point2 Second point
     * @return float Distance
     */
    public static function euclideanDistance(array $point1, array $point2): float
    {
        $sum = 0;
        $dimensions = min(count($point1), count($point2));

        for ($i = 0; $i < $dimensions; $i++) {
            $diff = $point1[$i] - $point2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Calculate total centroid shift
     *
     * @param array $oldCentroids Old centroids
     * @param array $newCentroids New centroids
     * @return float Total shift
     */
    protected static function calculateCentroidShift(array $oldCentroids, array $newCentroids): float
    {
        $totalShift = 0;

        foreach ($oldCentroids as $i => $oldCentroid) {
            if (isset($newCentroids[$i])) {
                $totalShift += self::euclideanDistance($oldCentroid, $newCentroids[$i]);
            }
        }

        return $totalShift;
    }

    /**
     * Format clusters with assigned points
     *
     * @param array $data Data points
     * @param array $assignments Cluster assignments
     * @param int $k Number of clusters
     * @return array Formatted clusters
     */
    protected static function formatClusters(array $data, array $assignments, int $k): array
    {
        $clusters = [];

        for ($i = 0; $i < $k; $i++) {
            $clusters[$i] = [
                'cluster_id' => $i,
                'points' => [],
                'size' => 0,
            ];
        }

        foreach ($assignments as $idx => $cluster) {
            $clusters[$cluster]['points'][] = $idx;
            $clusters[$cluster]['size']++;
        }

        return array_values($clusters);
    }

    /**
     * Calculate Silhouette Score
     *
     * Measures how similar a point is to its own cluster compared to other clusters.
     * Score ranges from -1 (wrong cluster) to +1 (perfect cluster).
     *
     * @param array $data Data points
     * @param array $assignments Cluster assignments
     * @return float Average silhouette score
     */
    public static function silhouetteScore(array $data, array $assignments): float
    {
        $n = count($data);
        if ($n < 2) {
            return 0;
        }

        $scores = [];

        for ($i = 0; $i < $n; $i++) {
            $point = $data[$i];
            $cluster = $assignments[$i];

            // Calculate a(i): average distance to points in same cluster
            $sameClusterDistances = [];
            for ($j = 0; $j < $n; $j++) {
                if ($i !== $j && $assignments[$j] === $cluster) {
                    $sameClusterDistances[] = self::euclideanDistance($point, $data[$j]);
                }
            }
            $a = empty($sameClusterDistances) ? 0 : array_sum($sameClusterDistances) / count($sameClusterDistances);

            // Calculate b(i): min average distance to points in other clusters
            $uniqueClusters = array_unique($assignments);
            $b = PHP_FLOAT_MAX;

            foreach ($uniqueClusters as $otherCluster) {
                if ($otherCluster !== $cluster) {
                    $otherClusterDistances = [];
                    for ($j = 0; $j < $n; $j++) {
                        if ($assignments[$j] === $otherCluster) {
                            $otherClusterDistances[] = self::euclideanDistance($point, $data[$j]);
                        }
                    }

                    if (!empty($otherClusterDistances)) {
                        $avgDist = array_sum($otherClusterDistances) / count($otherClusterDistances);
                        $b = min($b, $avgDist);
                    }
                }
            }

            // Calculate silhouette score for this point
            if ($a == 0 && $b == 0) {
                $scores[] = 0;
            } else {
                $scores[] = ($b - $a) / max($a, $b);
            }
        }

        return array_sum($scores) / count($scores);
    }

    /**
     * Normalize features to 0-1 range
     *
     * @param array $data Data points
     * @return array Normalized data and scaling parameters
     */
    public static function normalize(array $data): array
    {
        if (empty($data)) {
            return ['normalized' => [], 'min' => [], 'max' => []];
        }

        $dimensions = count($data[0]);
        $n = count($data);

        // Find min and max for each dimension
        $min = array_fill(0, $dimensions, PHP_FLOAT_MAX);
        $max = array_fill(0, $dimensions, PHP_FLOAT_MIN);

        foreach ($data as $point) {
            for ($d = 0; $d < $dimensions; $d++) {
                $min[$d] = min($min[$d], $point[$d]);
                $max[$d] = max($max[$d], $point[$d]);
            }
        }

        // Normalize
        $normalized = [];
        foreach ($data as $point) {
            $normalizedPoint = [];
            for ($d = 0; $d < $dimensions; $d++) {
                $range = $max[$d] - $min[$d];
                if ($range == 0) {
                    $normalizedPoint[$d] = 0.5; // All values same
                } else {
                    $normalizedPoint[$d] = ($point[$d] - $min[$d]) / $range;
                }
            }
            $normalized[] = $normalizedPoint;
        }

        return [
            'normalized' => $normalized,
            'min' => $min,
            'max' => $max,
        ];
    }
}

package com.jj.basicwebappapi;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/")
public class UserRatingController {

    private final UserRepository userRepository;
    private final MovieRepository movieRepository;
    private final RatingRepository ratingRepository;

    public UserRatingController(UserRepository userRepository, MovieRepository movieRepository, RatingRepository ratingRepository) {
        this.userRepository = userRepository;
        this.movieRepository = movieRepository;
        this.ratingRepository = ratingRepository;
    }

    private UserEntity getCurrentUser() {
        Authentication authentication = SecurityContextHolder.getContext().getAuthentication();
        if (authentication == null || !authentication.isAuthenticated()) {
            throw new SecurityException("Authentication required");
        }

        return userRepository.findByUsername(authentication.getName())
            .orElseThrow(() -> new SecurityException("User not found"));
    }

    @GetMapping("ratings")
    public ResponseEntity<Object> getMyRatings() {
        try {
            UserEntity currentUser = getCurrentUser();
            List<Map<String, Object>> ratings = ratingRepository.findByUserIdOrderByMovieNameAsc(currentUser.getId()).stream()
                .map(r -> {
                    Map<String, Object> ratingMap = new HashMap<>();
                    ratingMap.put("movie_id", r.getMovie().getId());
                    ratingMap.put("movie_name", r.getMovie().getMovieName());
                    ratingMap.put("score", r.getScore());
                    return ratingMap;
                })
                .collect(Collectors.toList());

            Map<String, Object> response = new HashMap<>();
            response.put("ratings", ratings);
            return ResponseEntity.ok(response);
        } catch (SecurityException ex) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(Map.of("error", "Authentication required"));
        }
    }

    @PostMapping("ratings")
    public ResponseEntity<Object> createRating(@RequestBody Map<String, Object> payload) {
        try {
            UserEntity currentUser = getCurrentUser();
            Integer movieId = payload.get("movie_id") instanceof Number ? ((Number) payload.get("movie_id")).intValue() : null;
            Integer score = payload.get("score") instanceof Number ? ((Number) payload.get("score")).intValue() : null;

            if (movieId == null || score == null) {
                return ResponseEntity.badRequest().body(Map.of("error", "movie_id and score are required"));
            }
            if (score < 1 || score > 5) {
                return ResponseEntity.badRequest().body(Map.of("error", "Score must be between 1 and 5"));
            }

            MovieEntity movie = movieRepository.findById(movieId)
                .orElseThrow(() -> new IllegalArgumentException("Movie not found"));

            if (ratingRepository.findByUserIdAndMovieId(currentUser.getId(), movieId).isPresent()) {
                return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", "You have already rated this movie"));
            }

            RatingEntity rating = new RatingEntity();
            rating.setUser(currentUser);
            rating.setMovie(movie);
            rating.setScore(score);
            ratingRepository.save(rating);

            return ResponseEntity.status(HttpStatus.CREATED).body(Map.of("message", "Rating added successfully"));
        } catch (SecurityException ex) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(Map.of("error", "Authentication required"));
        } catch (IllegalArgumentException ex) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", ex.getMessage()));
        }
    }

    @PutMapping("ratings")
    public ResponseEntity<Object> updateRating(@RequestBody Map<String, Object> payload) {
        try {
            UserEntity currentUser = getCurrentUser();
            Integer movieId = payload.get("movie_id") instanceof Number ? ((Number) payload.get("movie_id")).intValue() : null;
            Integer score = payload.get("score") instanceof Number ? ((Number) payload.get("score")).intValue() : null;

            if (movieId == null || score == null) {
                return ResponseEntity.badRequest().body(Map.of("error", "movie_id and score are required"));
            }
            if (score < 1 || score > 5) {
                return ResponseEntity.badRequest().body(Map.of("error", "Score must be between 1 and 5"));
            }

            RatingEntity rating = ratingRepository.findByUserIdAndMovieId(currentUser.getId(), movieId)
                .orElseThrow(() -> new IllegalArgumentException("Rating not found"));

            rating.setScore(score);
            ratingRepository.save(rating);

            return ResponseEntity.ok(Map.of("message", "Rating updated successfully"));
        } catch (SecurityException ex) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(Map.of("error", "Authentication required"));
        } catch (IllegalArgumentException ex) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", ex.getMessage()));
        }
    }

    @DeleteMapping("ratings")
    public ResponseEntity<Object> deleteRating(@RequestBody Map<String, Object> payload) {
        try {
            UserEntity currentUser = getCurrentUser();
            Integer movieId = payload.get("movie_id") instanceof Number ? ((Number) payload.get("movie_id")).intValue() : null;

            if (movieId == null) {
                return ResponseEntity.badRequest().body(Map.of("error", "movie_id is required"));
            }

            if (ratingRepository.findByUserIdAndMovieId(currentUser.getId(), movieId).isEmpty()) {
                return ResponseEntity.status(HttpStatus.NOT_FOUND).body(Map.of("error", "Rating not found"));
            }

            ratingRepository.deleteByUserIdAndMovieId(currentUser.getId(), movieId);
            return ResponseEntity.ok(Map.of("message", "Rating removed successfully"));
        } catch (SecurityException ex) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(Map.of("error", "Authentication required"));
        }
    }
}

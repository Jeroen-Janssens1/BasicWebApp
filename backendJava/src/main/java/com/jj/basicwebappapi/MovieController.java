package com.jj.basicwebappapi;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/")
public class MovieController {

    private final MovieRepository movieRepository;
   

    public MovieController(MovieRepository movieRepository) {
        this.movieRepository = movieRepository;
        
    }

    @GetMapping("movies")
    public ResponseEntity<Map<String, Object>> getMovies() {
        List<MovieEntity> movies = movieRepository.findAllByOrderByMovieNameAsc();

        List<Map<String, Object>> payload = movies.stream().map(movie -> {
            Map<String, Object> movieMap = new HashMap<>();
            movieMap.put("id", movie.getId());
            movieMap.put("name", movie.getMovieName());
            movieMap.put("description", movie.getMovieDescription());
            movieMap.put("avg_rating", movie.getAvgRating());
            movieMap.put("ratings_count", movie.getRatingsCount());
            return movieMap;
        }).collect(Collectors.toList());

        Map<String, Object> response = new HashMap<>();
        response.put("movies", payload);
        return ResponseEntity.ok(response);
    }

    @GetMapping("movie")
    public ResponseEntity<Object> getMovie(@RequestParam Integer id) {
        return movieRepository.findById(id)
            .map(movie -> {
                Map<String, Object> movieMap = new HashMap<>();
                movieMap.put("id", movie.getId());
                movieMap.put("name", movie.getMovieName());
                movieMap.put("description", movie.getMovieDescription());
                movieMap.put("avg_rating", movie.getAvgRating());
                movieMap.put("ratings_count", movie.getRatingsCount());

                Map<String, Object> response = new HashMap<>();
                response.put("movie", movieMap);
                return ResponseEntity.ok((Object) response);
            })
            .orElseGet(() -> ResponseEntity.status(HttpStatus.NOT_FOUND).body((Object) Map.of("error", "Movie not found")));
    }

    
}

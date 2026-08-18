package com.jj.basicwebappapi;

import java.util.List;
import java.util.Optional;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

public interface RatingRepository extends JpaRepository<RatingEntity, RatingId> {
    @Query("SELECT r FROM RatingEntity r JOIN FETCH r.user u JOIN FETCH r.movie m WHERE m.id = :movieId ORDER BY u.username ASC")
    List<RatingEntity> findByMovieIdOrderByUserUsernameAsc(@Param("movieId") Integer movieId);

    @Query("SELECT r FROM RatingEntity r JOIN FETCH r.movie m WHERE r.user.id = :userId ORDER BY m.movieName ASC")
    List<RatingEntity> findByUserIdOrderByMovieNameAsc(@Param("userId") Integer userId);

    Optional<RatingEntity> findByUserIdAndMovieId(Integer userId, Integer movieId);

    void deleteByUserIdAndMovieId(Integer userId, Integer movieId);
}
package com.jj.basicwebappapi;

import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;

public interface MovieRepository extends JpaRepository<MovieEntity, Integer> {
    List<MovieEntity> findAllByOrderByMovieNameAsc();
}

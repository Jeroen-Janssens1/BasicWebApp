package com.jj.basicwebappapi;

import java.io.Serializable;
import java.util.Objects;

public class RatingId implements Serializable {
    private Integer user;
    private Integer movie;

    public RatingId() {
    }

    public RatingId(Integer user, Integer movie) {
        this.user = user;
        this.movie = movie;
    }

    public Integer getUser() {
        return user;
    }

    public void setUser(Integer user) {
        this.user = user;
    }

    public Integer getMovie() {
        return movie;
    }

    public void setMovie(Integer movie) {
        this.movie = movie;
    }

    @Override
    public boolean equals(Object o) {
        if (this == o) return true;
        if (o == null || getClass() != o.getClass()) return false;
        RatingId ratingId = (RatingId) o;
        return Objects.equals(user, ratingId.user) && Objects.equals(movie, ratingId.movie);
    }

    @Override
    public int hashCode() {
        return Objects.hash(user, movie);
    }
}

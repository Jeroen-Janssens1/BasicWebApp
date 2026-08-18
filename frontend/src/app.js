const state = {
  user: null,
  movies: [],
  profileRatings: [],
  selectedMovie: null,
  selectedMovieRatings: []
};

const elements = {};

function setupElements() {
  elements.viewWelcome = document.getElementById('welcome-view');
  elements.viewProfile = document.getElementById('profile-view');
  elements.viewMovies = document.getElementById('movies-view');
  elements.viewMovieDetail = document.getElementById('movie-detail-view');

  elements.mainNav = document.getElementById('main-nav');
  elements.navProfile = document.getElementById('nav-profile');
  elements.navMovies = document.getElementById('nav-movies');
  elements.navLogout = document.getElementById('nav-logout');

  elements.loginForm = document.getElementById('login-form');
  elements.registerForm = document.getElementById('register-form');
  elements.profileUsername = document.getElementById('profile-username');
  elements.profileRatingsList = document.getElementById('profile-ratings-list');
  elements.profileSearch = document.getElementById('profile-search');
  elements.profileSortKey = document.getElementById('profile-sort-key');
  elements.profileSortDir = document.getElementById('profile-sort-dir');
  elements.deleteAccountBtn = document.getElementById('delete-account-btn');

  elements.movieSearch = document.getElementById('movie-search');
  elements.movieSortKey = document.getElementById('movie-sort-key');
  elements.movieSortDir = document.getElementById('movie-sort-dir');
  elements.moviesList = document.getElementById('movies-list');

  elements.movieDetail = document.getElementById('movie-detail-content');
  elements.backToMoviesBtn = document.getElementById('back-to-movies');
  elements.toast = document.getElementById('toast');
}

function showToast(message, isError = false) {
  const toast = elements.toast;
  toast.textContent = message;
  toast.classList.toggle('error', isError);
  toast.classList.add('show');
  window.clearTimeout(showToast.timeoutId);
  showToast.timeoutId = window.setTimeout(() => {
    toast.classList.remove('show');
  }, 2600);
}

function showView(viewName) {
  const viewMap = {
    welcome: elements.viewWelcome,
    profile: elements.viewProfile,
    movies: elements.viewMovies,
    detail: elements.viewMovieDetail
  };

  Object.values(viewMap).forEach((view) => view.classList.remove('active'));
  if (viewMap[viewName]) {
    viewMap[viewName].classList.add('active');
  }

  const loggedIn = Boolean(state.user);
  elements.mainNav.classList.toggle('hidden', !loggedIn);
  if (!loggedIn) {
    elements.navProfile.setAttribute('aria-pressed', 'false');
    elements.navMovies.setAttribute('aria-pressed', 'false');
  }
}

async function requestJson(url, options = {}) {
  const finalOptions = {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.headers || {})
    }
  };

  if (options.body && !finalOptions.headers['Content-Type']) {
    finalOptions.headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(url, finalOptions);
  const text = await response.text();
  let payload = {};

  if (text) {
    try {
      payload = JSON.parse(text);
    } catch (error) {
      payload = { error: text };
    }
  }

  if (!response.ok) {
    throw new Error(payload.error || 'Request failed');
  }

  return payload;
}

async function checkLoginState() {
  try {
    const result = await requestJson('/api/user', { method: 'GET' });
    state.user = result.user?.username || null;
    if (state.user) {
      elements.profileUsername.textContent = `Welcome, ${state.user}!`;
      showView('profile');
      await loadProfileRatings();
    } else {
      showView('welcome');
    }
  } catch (error) {
    state.user = null;
    showView('welcome');
  }
  await loadMovies();
}

async function loadMovies() {
  try {
    const result = await requestJson('/api/movies', { method: 'GET' });
    state.movies = Array.isArray(result.movies) ? result.movies : [];
    renderMovies();
  } catch (error) {
    showToast(error.message, true);
  }
}

async function loadProfileRatings() {
  if (!state.user) {
    state.profileRatings = [];
    renderProfileRatings();
    return;
  }

  try {
    const result = await requestJson('/api/ratings', { method: 'GET' });
    state.profileRatings = Array.isArray(result.ratings) ? result.ratings : [];
    renderProfileRatings();
  } catch (error) {
    showToast(error.message, true);
  }
}

function getFilteredAndSortedMovies() {
  const search = elements.movieSearch.value.trim().toLowerCase();
  const key = elements.movieSortKey.value;
  const direction = elements.movieSortDir.value === 'asc' ? 1 : -1;

  return [...state.movies]
    .filter((movie) => movie.name?.toLowerCase().includes(search))
    .sort((a, b) => {
      const left = key === 'name' ? (a.name || '').toLowerCase() : Number(a.avg_rating || 0);
      const right = key === 'name' ? (b.name || '').toLowerCase() : Number(b.avg_rating || 0);

      if (key === 'name') {
        return left.localeCompare(right) * direction;
      }

      return (Number(left) - Number(right)) * direction;
    });
}

function renderMovies() {
  const filteredMovies = getFilteredAndSortedMovies();

  if (!filteredMovies.length) {
    elements.moviesList.innerHTML = '<div class="empty-state">No movies match your search.</div>';
    return;
  }

  elements.moviesList.innerHTML = filteredMovies.map((movie) => `
    <button class="movie-card" data-action="open-movie" data-id="${movie.id}">
      <div class="movie-card-header">
        <h3>${escapeHtml(movie.name || 'Untitled')}</h3>
        <span class="movie-score">${Number(movie.avg_rating || 0).toFixed(1)}</span>
      </div>
      <p>${escapeHtml(movie.description || 'No description available.')}</p>
      <div class="movie-meta">
        <span>${movie.ratings_count || 0} ratings</span>
      </div>
    </button>
  `).join('');
}

function getFilteredAndSortedProfileRatings() {
  const search = elements.profileSearch.value.trim().toLowerCase();
  const key = elements.profileSortKey.value;
  const direction = elements.profileSortDir.value === 'asc' ? 1 : -1;

  return [...state.profileRatings]
    .filter((rating) => (rating.movie_name || '').toLowerCase().includes(search))
    .sort((a, b) => {
      const left = key === 'movie_name' ? (a.movie_name || '').toLowerCase() : Number(a.score || 0);
      const right = key === 'movie_name' ? (b.movie_name || '').toLowerCase() : Number(b.score || 0);

      if (key === 'movie_name') {
        return left.localeCompare(right) * direction;
      }

      return (Number(left) - Number(right)) * direction;
    });
}

function renderProfileRatings() {
  const filteredRatings = getFilteredAndSortedProfileRatings();

  if (!state.user) {
    elements.profileUsername.textContent = 'Please sign in to view your profile.';
    elements.profileRatingsList.innerHTML = '';
    return;
  }

  elements.profileUsername.textContent = `Welcome, ${state.user}!`;

  if (!filteredRatings.length) {
    elements.profileRatingsList.innerHTML = '<div class="empty-state">You have not rated any movies yet.</div>';
    return;
  }

  elements.profileRatingsList.innerHTML = filteredRatings.map((rating) => `
    <div class="rating-card">
      <div class="rating-header">
        <div>
          <h4>${escapeHtml(rating.movie_name || 'Movie')}</h4>
          <p>Your score: ${Number(rating.score || 0)}/5</p>
        </div>
        <div class="rating-actions">
          <button class="secondary-btn" data-action="edit-rating" data-id="${rating.movie_id}">Edit</button>
          <button class="danger-btn" data-action="delete-rating" data-id="${rating.movie_id}">Remove</button>
        </div>
      </div>
    </div>
  `).join('');
}

async function openMovieDetail(movieId) {
  try {
    const movieResult = await requestJson(`/api/movie?id=${movieId}`, { method: 'GET' });
    const ratingsResult = await requestJson(`/api/movie/ratings?id=${movieId}`, { method: 'GET' });

    state.selectedMovie = movieResult.movie || null;
    state.selectedMovieRatings = Array.isArray(ratingsResult.ratings) ? ratingsResult.ratings : [];

    if (!state.selectedMovie) {
      throw new Error('Movie not found');
    }

    const movie = state.selectedMovie;
    const ratings = state.selectedMovieRatings;

    const ratedByList = ratings.length
      ? ratings.map((rating) => `
          <li>
            <strong>${escapeHtml(rating.username || 'User')}</strong>
            <span>${Number(rating.score || 0)}/5</span>
          </li>
        `).join('')
      : '<li>No ratings yet.</li>';

    let ratingFormHtml = '';
    if (state.user) {
      ratingFormHtml = `
        <div class="rating-form">
          <h3>Rate this movie</h3>
          <div class="inline-form">
            <select id="movie-rating-score">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3" selected>3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
            <button class="primary-btn" id="submit-movie-rating">Submit rating</button>
          </div>
        </div>
      `;
    }

    elements.movieDetail.innerHTML = `
      <div class="movie-detail-card">
        <div class="detail-header">
          <h2>${escapeHtml(movie.name || 'Movie')}</h2>
          <span class="movie-score large">${Number(movie.avg_rating || 0).toFixed(1)}</span>
        </div>
        <p class="movie-description">${escapeHtml(movie.description || 'No description available.')}</p>
        <div class="movie-meta-row">
          <span>${movie.ratings_count || 0} ratings</span>
          <span>Average: ${Number(movie.avg_rating || 0).toFixed(1)}/5</span>
        </div>
        ${ratingFormHtml}
        <div class="ratings-panel">
          <h3>Previous ratings</h3>
          <ul class="rating-list">${ratedByList}</ul>
        </div>
      </div>
    `;

    const submitButton = document.getElementById('submit-movie-rating');
    if (submitButton) {
      submitButton.addEventListener('click', async () => {
        const select = document.getElementById('movie-rating-score');
        const score = Number(select.value);
        if (!movie.id) {
          return;
        }
        try {
          await requestJson('/api/ratings', {
            method: 'POST',
            body: JSON.stringify({ movie_id: Number(movie.id), score })
          });
          showToast('Rating added successfully');
          await loadProfileRatings();
          await openMovieDetail(Number(movie.id));
        } catch (error) {
          showToast(error.message, true);
        }
      });
    }

    showView('detail');
  } catch (error) {
    showToast(error.message, true);
  }
}

async function handleLogin(event) {
  event.preventDefault();
  const formData = new FormData(elements.loginForm);
  const username = String(formData.get('username') || '').trim();
  const password = String(formData.get('password') || '');

  if (!username || !password) {
    showToast('Username and password are required', true);
    return;
  }

  try {
    const result = await requestJson('/api/login', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });

    state.user = result.user?.username || username;
    elements.loginForm.reset();
    showToast('Login successful');
    showView('profile');
    await loadProfileRatings();
  } catch (error) {
    showToast(error.message, true);
  }
}

async function handleRegister(event) {
  event.preventDefault();
  const formData = new FormData(elements.registerForm);
  const username = String(formData.get('username') || '').trim();
  const password = String(formData.get('password') || '');

  if (!username || !password) {
    showToast('Username and password are required', true);
    return;
  }

  try {
    await requestJson('/api/register', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });
    elements.registerForm.reset();
    showToast('Account created successfully');
  } catch (error) {
    showToast(error.message, true);
  }
}

async function logoutUser() {
  try {
    await requestJson('/api/logout', { method: 'POST' });
  } catch (error) {
    // ignore logout failure and continue closing the session locally
  }

  state.user = null;
  state.profileRatings = [];
  elements.profileSearch.value = '';
  elements.profileSortKey.value = 'movie_name';
  elements.profileSortDir.value = 'asc';
  showView('welcome');
  renderProfileRatings();
  showToast('Logged out');
}

async function deleteAccount() {
  if (!state.user) {
    return;
  }

  const confirmDelete = window.confirm('Are you sure you want to delete your account? This cannot be undone.');
  if (!confirmDelete) {
    return;
  }

  try {
    await requestJson('/api/user', { method: 'DELETE' });
    state.user = null;
    state.profileRatings = [];
    showView('welcome');
    renderProfileRatings();
    showToast('Account deleted');
  } catch (error) {
    showToast(error.message, true);
  }
}

async function editRating(movieId) {
  const existing = state.profileRatings.find((rating) => Number(rating.movie_id) === Number(movieId));
  if (!existing) {
    return;
  }

  const nextScore = window.prompt('Update your score (1-5):', String(existing.score || 3));
  const scoreValue = Number(nextScore);

  if (!Number.isInteger(scoreValue) || scoreValue < 1 || scoreValue > 5) {
    showToast('Please enter a valid score between 1 and 5', true);
    return;
  }

  try {
    await requestJson('/api/ratings', {
      method: 'PUT',
      body: JSON.stringify({ movie_id: Number(movieId), score: scoreValue })
    });
    showToast('Rating updated');
    await loadProfileRatings();
  } catch (error) {
    showToast(error.message, true);
  }
}

async function removeRating(movieId) {
  const confirmDelete = window.confirm('Remove this rating?');
  if (!confirmDelete) {
    return;
  }

  try {
    await requestJson('/api/ratings', {
      method: 'DELETE',
      body: JSON.stringify({ movie_id: Number(movieId) })
    });
    showToast('Rating removed');
    await loadProfileRatings();
    if (state.selectedMovie && Number(state.selectedMovie.id) === Number(movieId)) {
      await openMovieDetail(Number(movieId));
    }
  } catch (error) {
    showToast(error.message, true);
  }
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function attachEvents() {
  elements.loginForm.addEventListener('submit', handleLogin);
  elements.registerForm.addEventListener('submit', handleRegister);
  elements.deleteAccountBtn.addEventListener('click', deleteAccount);
  elements.navProfile.addEventListener('click', () => {
    if (state.user) {
      showView('profile');
      loadProfileRatings();
    }
  });
  elements.navMovies.addEventListener('click', () => showView('movies'));
  elements.navLogout.addEventListener('click', logoutUser);
  elements.backToMoviesBtn.addEventListener('click', () => showView('movies'));

  elements.movieSearch.addEventListener('input', renderMovies);
  elements.movieSortKey.addEventListener('change', renderMovies);
  elements.movieSortDir.addEventListener('change', renderMovies);

  elements.profileSearch.addEventListener('input', renderProfileRatings);
  elements.profileSortKey.addEventListener('change', renderProfileRatings);
  elements.profileSortDir.addEventListener('change', renderProfileRatings);

  document.addEventListener('click', async (event) => {
    const actionTarget = event.target.closest('[data-action]');
    if (!actionTarget) {
      return;
    }

    const action = actionTarget.dataset.action;
    const movieId = Number(actionTarget.dataset.id);

    if (action === 'open-movie') {
      await openMovieDetail(movieId);
      return;
    }

    if (action === 'edit-rating') {
      await editRating(movieId);
      return;
    }

    if (action === 'delete-rating') {
      await removeRating(movieId);
    }
  });
}

document.addEventListener('DOMContentLoaded', async () => {
  setupElements();
  attachEvents();
  await checkLoginState();
});

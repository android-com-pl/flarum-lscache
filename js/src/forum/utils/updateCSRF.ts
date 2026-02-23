import app from 'flarum/forum/app';

export default () => {
  if (app.session.user) {
    return;
  }

  // We do not use `app.request` here because that function returns only the body and we need headers
  fetch(`${app.forum.attribute('apiUrl')}/lscache-csrf`).then((res) => {
    const token = res.headers.get('X-CSRF-Token');

    if (token) {
      app.session.csrfToken = token;
    }
  });
};

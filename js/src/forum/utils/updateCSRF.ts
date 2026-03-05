import app from 'flarum/forum/app';

export default async () => {
  if (app.session.user) {
    return;
  }

  // `app.request` only returns the body, but headers are needed here.
  const res = await fetch(`${app.forum.attribute('apiUrl')}/lscache-csrf`);
  const token = res.headers.get('X-CSRF-Token');

  if (token) {
    app.session.csrfToken = token;
  }
};

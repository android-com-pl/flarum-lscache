import app from 'flarum/admin/app';

app.initializers.add('acpl/flarum-lscache', () => {
  console.log('[acpl/flarum-lscache] Hello, admin!');
});

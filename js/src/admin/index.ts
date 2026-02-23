import app from 'flarum/admin/app';
import addPurgeLSCacheButton from './addPurgeLSCacheButton';

export { default as extend } from './extend';

app.initializers.add('acpl-lscache', () => {
  addPurgeLSCacheButton();
});

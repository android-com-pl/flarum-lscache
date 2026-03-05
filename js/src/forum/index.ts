import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Modal from 'flarum/common/components/Modal';
import updateCSRF from './utils/updateCSRF';
import extendDiscussionControls from './extendDiscussionControls';

app.initializers.add('acpl-lscache', () => {
  // Extend all modals, including those from external extensions
  extend(Modal.prototype, 'oninit', updateCSRF);
  extendDiscussionControls();
});

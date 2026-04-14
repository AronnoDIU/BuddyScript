import { useRef, useState } from 'react';
import { Image, Radio, Smile } from 'lucide-react';

export default function CreatePagePost({ page, onCreatePost }) {
  const [content, setContent] = useState('');
  const [posting, setPosting] = useState(false);
  const [error, setError] = useState('');
  const [imageFile, setImageFile] = useState(null);
  const fileRef = useRef(null);

  const submit = async (event) => {
    event.preventDefault();
    if (!content.trim()) return;

    setPosting(true);
    setError('');
    try {
      await onCreatePost?.({
        content: content.trim(),
        image: imageFile,
      });
      setContent('');
      setImageFile(null);
      if (fileRef.current) {
        fileRef.current.value = '';
      }
    } catch (submitError) {
      setError(submitError?.response?.data?.message || 'Failed to publish post.');
    } finally {
      setPosting(false);
    }
  };

  return (
    <form className="pages-create-post" onSubmit={submit}>
      <div className="pages-create-post__top">
        <span className="pages-create-post__avatar">{page.name?.charAt(0)?.toUpperCase() || 'P'}</span>
        <input
          value={content}
          onChange={(event) => setContent(event.target.value)}
          placeholder={`What's on your mind, ${page.name}?`}
        />
      </div>

      {imageFile && <p className="pages-create-post__helper">Image attached: {imageFile.name}</p>}
      {error && <p className="pages-create-post__error">{error}</p>}

      <div className="pages-create-post__actions">
        <button type="button" onClick={() => {}}>
          <Radio size={16} color="#F02849" />
          Live Video
        </button>
        <button type="button" onClick={() => fileRef.current?.click()}>
          <Image size={16} color="#45BD62" />
          Photo
        </button>
        <button type="button" onClick={() => {}}>
          <Smile size={16} color="#F7B928" />
          Feeling
        </button>

        <button type="submit" className="pages-create-post__submit" disabled={posting || !content.trim()}>
          {posting ? 'Posting...' : 'Post'}
        </button>
      </div>

      <input
        ref={fileRef}
        type="file"
        accept="image/*"
        hidden
        onChange={(event) => setImageFile(event.target.files?.[0] || null)}
      />
    </form>
  );
}


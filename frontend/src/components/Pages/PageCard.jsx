import { Globe, User } from 'lucide-react';

export default function PageCard({ page, selected, onSelect, onToggleFollow }) {
  const handleSelect = () => onSelect?.(page);

  return (
    <div
      className={`pages-card ${selected ? 'is-selected' : ''}`}
      role="button"
      tabIndex={0}
      onClick={handleSelect}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleSelect();
        }
      }}
    >
      <span className="pages-card__avatar" aria-hidden="true">
        {page.avatarUrl ? (
          <img src={page.avatarUrl} alt="" />
        ) : (
          page.name?.charAt(0)?.toUpperCase() || 'P'
        )}
      </span>

      <span className="pages-card__content">
        <strong>{page.name}</strong>
        <span>{page.category || 'Community'}</span>
        <span>{(page.followersCount || 0).toLocaleString()} followers</span>
      </span>

      <span className="pages-card__meta">
        <span className="pages-card__chip">
          <Globe size={12} />
          Page
        </span>
        {!page.isOwner && (
          <button
            type="button"
            className={`pages-card__follow ${page.isFollowing ? 'is-following' : ''}`}
            onClick={(event) => {
              event.stopPropagation();
              onToggleFollow?.(page);
            }}
          >
            <User size={12} />
            {page.isFollowing ? 'Following' : 'Follow'}
          </button>
        )}
      </span>
    </div>
  );
}


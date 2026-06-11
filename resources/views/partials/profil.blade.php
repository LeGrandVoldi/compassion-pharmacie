<div class="header-right">
        <div class="user-info" id="openProfileModal" style="cursor:pointer;">
            <div class="avatar">
                @if($profileImage)
                    <img
                        src="{{ $profileImage }}"
                        alt="Profil"
                        id="headerProfileImage"
                        style="width:38px;height:38px;border-radius:50%;object-fit:cover;"
                    >
                @else
                    <svg viewBox="0 0 40 40" fill="none" width="38" height="38" id="headerProfileSvg">
                        <circle cx="20" cy="20" r="20" fill="#dbeafe"/>
                        <circle cx="20" cy="15" r="7" fill="#60a5fa"/>
                        <ellipse cx="20" cy="33" rx="12" ry="7" fill="#60a5fa"/>
                        <circle cx="20" cy="15" r="5.5" fill="#1d4ed8"/>
                    </svg>
                @endif
            </div>
            <div>
                <div class="user-name">{{ $nom }}</div>
                <div class="user-role">{{ $role }}</div>
            </div>
        </div>
        <button class="logout-btn header-action-btn" title="Déconnexion">
          <i class="bi bi-power"></i>
        </button>
</div>



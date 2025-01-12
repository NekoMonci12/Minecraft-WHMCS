
# Minecraft WHMCS (Pterodactyl/Pelican)

An WHMCS Modules For Monitize Minecraft Server Cosmetics/Items, With Pterodactyl/Pelican Panel API Integration.
## How To Install
    1. Navigate To `WHMCS-ROOT/modules/servers/` and upload the `panelstores` folder there
    ```
    WHMCS-Root/
        └── modules/
            └── servers/
                └── panelstores/
                    ├── panelstores.php
                    └── whmcs.json
    ```
    2. Navigate to `Your WHMCS Sites > System Settings > Servers` and Create New Servers
    3. Fill The Configuration & Save Your Changes
    ```
    hostname: YOUR_PTERODACTYL_DOMAIN
    password: YOUR_PTERODACTYL_USER-API-KEY
    ```
    4. Navigate To `Your WHMCS Sites > System Settings > Servers` and Create New Groups
    5. Then Choose the created server and press the Add button
    6. Navigate to `Your WHMCS Sites > System Settings > Products/Services > Products/Services`
    7. Create your Product with the type of Other, Fill the configuration & save it
    8. Navigate `Module` tab on your Product, choose for Module Name `Panel Stores` and for the Server Group the group you created in step 5
    9. Navigate `Custom Fields` tab on your Product, Do it like on this image shown
    ![Image](https://i.imgur.com/Ng6qlme.png)
    10. The Product now is ready to use

## How To Get Pterodactyl Users API
    1. Go To Your Pterodactyl Panel
    2. Click Your Accounts Profiles
    3. Navigate To `API Credentials` Tab
    4. Click The Create Button & Copy The API Key
    ![image](https://i.imgur.com/KlgVpEH.png)
## Authors

- [@nekomonci12](https://www.github.com/nekomonci12)


# BountyPM5
A simple plugin with pocketmine pm5 Bounty

# BountyPM

A bounty system plugin for PocketMine-MP servers that allows players to set bounties on other players.

## 📋 Features

- **Bounty System**: Players can set bounties on other players
- **Bounty Accumulation**: Bounties stack if multiple players set bounties on the same target
- **Bounty List**: View all active bounties sorted by amount
- **Economy Integration**: Uses EconomyAPI for handling transactions
- **Administrative Management**: Administrators can remove bounties
- **Notifications**: Broadcast messages when bounties are set, updated, or claimed

## 🔧 Requirements

- **PocketMine-MP**: API 5.0.0 or higher
- **EconomyAPI**: Required plugin for economy system

## 📦 Installation

1. Download the plugin `.phar` file
2. Place it in your server's `plugins/` folder
3. Make sure you have **EconomyAPI** installed and working
4. Restart your server

## 📖 Commands

### `/bounty create <player> <amount>`
Sets a bounty on a specific player.

**Usage**: `/bounty create Steve 1000`
- Deducts money from your account
- If a bounty already exists, it adds to the existing amount
- You cannot set bounties on yourself

### `/bounty list`
Shows all active bounties sorted from highest to lowest.

**Features**:
- Shows top 10 bounties
- Numbered format with player names and amounts
- Indicates if there are more bounties available

### `/bounty remove <player>`
Removes a specific bounty (administrators only).

**Usage**: `/bounty remove Steve`
- Completely removes the bounty
- Broadcasts the removal to all players
- Can be executed from console

## 🛡️ Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| `bountypm.command.bounty` | Allows general use of /bounty command | `op` |
| `bountypm.command.bounty.create` | Allows creating bounties | `true` |
| `bountypm.command.bounty.list` | Allows viewing bounty list | `true` |
| `bountypm.command.bounty.remove` | Allows removing bounties | `op` |

## 🎮 How It Works

1. **Setting Bounties**: Players use `/bounty create <player> <amount>` to set a bounty
2. **Claiming Bounties**: When a player with an active bounty is killed by another player, the killer automatically receives the bounty money
3. **Bounty Removal**: Bounties are automatically removed when claimed, or can be manually removed by administrators

## 📁 Configuration

The plugin automatically creates a `bounties.yml` file in the plugin data folder to store active bounties. No manual configuration is required.

## 🔄 Bounty Lifecycle

1. **Creation**: Player sets bounty → Money deducted → Bounty stored → Server announcement
2. **Accumulation**: Additional bounties on same target → Amounts stack → Updated announcement
3. **Claiming**: Target player dies to another player → Bounty money given to killer → Bounty removed → Claim announcement
4. **Removal**: Administrator removes bounty → Bounty deleted → Removal announcement

## 📝 Example Usage

```
/bounty create Notch 5000
> ✓ Bounty set! You spent $5000. Notch now has a bounty of $5000.

/bounty list
> --- Active Bounties ---
> #1: Notch - $5,000
> #2: Steve - $2,500
> #3: Alex - $1,000

/bounty remove Notch
> ✓ Bounty removed! The $5000 bounty on Notch has been removed.
```

## 🚨 Important Notes

- Players cannot set bounties on themselves
- Bounty amounts must be positive numbers
- Players must have sufficient funds to set bounties
- EconomyAPI is required for the plugin to function
- Console can execute `list` and `remove` commands
- Only players killed by other players trigger bounty claims (not environmental deaths)

## 👨‍💻 Author

**Phoenix** - [GitHub](https://github.com/Phoenix4041)

## 📄 License

This plugin is provided as-is. Feel free to modify and distribute according to your needs.

## 🤝 Contributing

If you find bugs or want to contribute improvements, please create an issue or pull request on the GitHub repository.

## 📞 Support

For support, please visit the GitHub repository or contact the author through the provided channels.
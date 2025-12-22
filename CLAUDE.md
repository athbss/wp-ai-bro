# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

This WordPress AI Assistant plugin doesn't have traditional npm/composer build commands but uses GitHub Actions for deployment:

- **Manual ZIP Build**: Use GitHub Actions workflow `.github/workflows/build-zip.yml`
- **Version Updates**: Update version in `wordpress-ai-assistant.php` and `plugin-info.json` simultaneously
- **Release Process**: Follow semantic versioning - update CHANGELOG.md (Hebrew), create git tag, push to trigger automated build

## Architecture Overview

### Core Plugin Structure
- **Entry Point**: `wordpress-ai-assistant.php` - Main plugin file with metadata and initialization
- **Core Orchestrator**: `AT_WordPress_AI_Assistant_Core` in `includes/class-core.php` - Central plugin controller
- **Hook Management**: `AT_WordPress_AI_Assistant_Loader` - Manages all WordPress hooks and filters
- **Admin/Public Split**: Separate classes for admin (`admin/class-admin.php`) and public functionality (`public/class-public.php`)

### AI Infrastructure (`includes/ai/`)
- **AI Manager**: `AT_AI_Manager` (singleton) - Central coordinator for all AI operations
- **Provider System**: Abstract base class with concrete implementations for:
  - OpenAI/GPT (`AT_OpenAI_Provider`)
  - Anthropic/Claude (`AT_Anthropic_Provider`) 
  - Google AI/Gemini (`AT_Google_Provider`)
- **Usage Tracker**: `AT_AI_Usage_Tracker` - Token consumption and cost monitoring

### Feature System (`includes/features/`)
- **Image Alt Generator**: `AT_Image_Alt_Generator` - Automatic alt text for media uploads
- **Text Translator**: `AT_Text_Translator` - Context-aware translation
- **Auto Tagger**: `AT_Auto_Tagger` - Intelligent content categorization

### Database Schema
Custom tables created on activation:
- `wp_ai_assistant_settings` - Plugin configuration
- `wp_ai_assistant_logs` - Activity logging  
- `wp_ai_assistant_media_meta` - AI-generated metadata
- `wp_ai_assistant_usage` - Usage tracking

## Key Development Patterns

### Version Management
- **Semantic Versioning**: MAJOR.MINOR.PATCH for AI features
- **Dual Version Updates**: Always update both `wordpress-ai-assistant.php` header and `plugin-info.json`
- **Automated Deployment**: GitHub Actions builds ZIP on tag push

### AI Provider Integration
- Extend `AT_AI_Provider` abstract class for new providers
- Register providers through `AT_AI_Manager::register_provider()`
- Implement required methods: `make_request()`, `get_models()`, `calculate_cost()`

### Security Considerations
- API keys stored encrypted in WordPress options
- Capability checks: `edit_posts` for content features, `manage_options` for settings
- Prepared statements for all database operations
- Input sanitization for AI prompt data

### Multilingual Support
- Hebrew language primary focus with RTL support
- WPML/Polylang compatibility for translated content
- Auto-language detection for content processing

## WordPress-Specific Integration Points

### Hook Integration
- Media upload hooks for automatic alt text generation
- Post save hooks for auto-tagging
- Admin menu integration through standard WordPress APIs
- AJAX endpoints for real-time AI processing

### Settings Architecture
- WordPress Options API for configuration storage
- Settings pages use WordPress Settings API
- Encrypted storage for sensitive API credentials

## Current Development State

- **Version**: 1.2.0 with latest AI models (GPT-4o, Claude 3.5 Sonnet, Gemini 2.0)
- **Recent Updates**: Enhanced multilingual support, general prompt instructions, visual style settings
- **Active Development**: Uncommitted changes in core files suggest ongoing feature development